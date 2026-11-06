"""Shared PyTorch model architecture and image preprocessing."""

from __future__ import annotations

import io
from pathlib import Path
from typing import Any

import torch
from PIL import Image
from torch import nn
from torchvision import models, transforms
from torchvision.transforms import InterpolationMode

IMAGE_SIZE = 224
IMAGE_RESIZE = 256
MIN_CONFIDENCE = 0.70
IMAGENET_MEAN = (0.485, 0.456, 0.406)
IMAGENET_STD = (0.229, 0.224, 0.225)


def build_model(num_classes: int, pretrained: bool = True) -> nn.Module:
    weights = models.MobileNet_V3_Small_Weights.DEFAULT if pretrained else None
    model = models.mobilenet_v3_small(weights=weights)

    for parameter in model.features.parameters():
        parameter.requires_grad = False
    for parameter in model.features[-4:].parameters():
        parameter.requires_grad = True

    input_features = model.classifier[-1].in_features
    model.classifier[-1] = nn.Linear(input_features, num_classes)
    return model


def training_transform() -> transforms.Compose:
    return transforms.Compose(
        [
            transforms.Resize(IMAGE_RESIZE, interpolation=InterpolationMode.BICUBIC),
            transforms.RandomCrop(IMAGE_SIZE),
            transforms.RandomHorizontalFlip(),
            transforms.RandomRotation(8),
            transforms.ColorJitter(
                brightness=0.15, contrast=0.15, saturation=0.15, hue=0.03
            ),
            transforms.ToTensor(),
            transforms.Normalize(IMAGENET_MEAN, IMAGENET_STD),
        ]
    )


def evaluation_transform() -> transforms.Compose:
    return transforms.Compose(
        [
            transforms.Resize(IMAGE_RESIZE, interpolation=InterpolationMode.BICUBIC),
            transforms.CenterCrop(IMAGE_SIZE),
            transforms.ToTensor(),
            transforms.Normalize(IMAGENET_MEAN, IMAGENET_STD),
        ]
    )


def load_model(
    checkpoint_path: Path, device: torch.device
) -> tuple[nn.Module, list[str], transforms.Compose]:
    if not checkpoint_path.is_file():
        raise FileNotFoundError(f"Trained model not found: {checkpoint_path}")

    checkpoint_data: Any = torch.load(
        checkpoint_path, map_location=device, weights_only=True
    )
    if not isinstance(checkpoint_data, dict):
        raise ValueError(f"Invalid model checkpoint: {checkpoint_path}")
    checkpoint: dict[str, Any] = checkpoint_data
    classes = checkpoint.get("classes")
    state_dict = checkpoint.get("state_dict")
    if (
        checkpoint.get("architecture") != "mobilenet_v3_small"
        or checkpoint.get("image_size") != IMAGE_SIZE
        or not isinstance(classes, list)
        or len(classes) != 3
        or not all(isinstance(name, str) for name in classes)
        or set(classes) != {"organic", "paper", "plastic"}
        or not isinstance(state_dict, dict)
    ):
        raise ValueError(f"Invalid model checkpoint: {checkpoint_path}")

    model = build_model(len(classes), pretrained=False)
    model.load_state_dict(state_dict, strict=True)
    model.to(device)
    model.eval()
    return model, classes, evaluation_transform()


def load_rgb_image(image_bytes: bytes) -> Image.Image:
    with Image.open(io.BytesIO(image_bytes)) as image:
        image.load()
        return image.convert("RGB")
