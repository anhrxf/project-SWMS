"""Contract tests for the FastAPI image-classification endpoint."""

from __future__ import annotations

import io
import unittest
from unittest.mock import patch

import torch
from fastapi.testclient import TestClient
from PIL import Image
from torch import nn
from torchvision.transforms import ToTensor

from fastapi_app import MAX_IMAGE_BYTES, app


class FixedModel(nn.Module):
    def forward(self, images: torch.Tensor) -> torch.Tensor:
        return torch.tensor([[0.1, 0.2, 3.7]], device=images.device).repeat(
            images.shape[0], 1
        )


class UncertainModel(nn.Module):
    def forward(self, images: torch.Tensor) -> torch.Tensor:
        return torch.tensor([[0.1, 0.2, 0.3]], device=images.device).repeat(
            images.shape[0], 1
        )


def _image_bytes() -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", (32, 32), color="blue").save(buffer, format="JPEG")
    return buffer.getvalue()


class PredictionEndpointTest(unittest.TestCase):
    def setUp(self) -> None:
        self.model_loader = patch(
            "fastapi_app.load_model",
            return_value=(
                FixedModel(),
                ["organic", "paper", "plastic"],
                ToTensor(),
            ),
        )
        self.model_loader.start()
        self.client = TestClient(app)
        self.client.__enter__()

    def tearDown(self) -> None:
        self.client.__exit__(None, None, None)
        self.model_loader.stop()

    def test_health_and_prediction_return_model_output(self) -> None:
        health = self.client.get("/health")
        self.assertEqual(200, health.status_code)
        self.assertTrue(health.json()["model_loaded"])

        response = self.client.post(
            "/predict",
            files={"file": ("sample.jpg", _image_bytes(), "image/jpeg")},
        )
        self.assertEqual(200, response.status_code)
        self.assertEqual("classified", response.json()["status"])
        self.assertEqual("plastic", response.json()["category"])
        expected_confidence = round(
            float(torch.softmax(torch.tensor([0.1, 0.2, 3.7]), dim=0)[2]) * 100,
            2,
        )
        self.assertEqual(expected_confidence, response.json()["confidence"])
        self.assertEqual(3, len(response.json()["breakdown"]))

    def test_low_confidence_prediction_is_returned_as_uncertain(self) -> None:
        self.client.app.state.predictor = (
            UncertainModel(),
            ["organic", "paper", "plastic"],
            ToTensor(),
        )

        response = self.client.post(
            "/predict",
            files={"file": ("sample.jpg", _image_bytes(), "image/jpeg")},
        )

        self.assertEqual(200, response.status_code)
        self.assertEqual("uncertain", response.json()["status"])
        self.assertIsNone(response.json()["category"])
        self.assertIsNone(response.json()["recommendation"])
        self.assertIn("belum cukup yakin", response.json()["message"])

    def test_empty_invalid_and_oversized_files_are_rejected(self) -> None:
        empty = self.client.post(
            "/predict", files={"file": ("empty.jpg", b"", "image/jpeg")}
        )
        invalid = self.client.post(
            "/predict", files={"file": ("invalid.jpg", b"not an image", "image/jpeg")}
        )
        oversized = self.client.post(
            "/predict",
            files={
                "file": (
                    "large.jpg",
                    b"x" * (MAX_IMAGE_BYTES + 1),
                    "image/jpeg",
                )
            },
        )

        self.assertEqual(400, empty.status_code)
        self.assertEqual(422, invalid.status_code)
        self.assertEqual(413, oversized.status_code)


if __name__ == "__main__":
    unittest.main()
