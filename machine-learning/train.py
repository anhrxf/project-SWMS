"""Train and evaluate a PyTorch waste-image classifier."""

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import os
import random
from copy import deepcopy
from pathlib import Path
from typing import Any

import matplotlib

matplotlib.use("Agg")

import matplotlib.pyplot as plt
import numpy as np
import torch
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import train_test_split
from torch import nn
from torch.utils.data import DataLoader, Subset
from torchvision.datasets import ImageFolder

from classifier import (
    MIN_CONFIDENCE,
    build_model,
    evaluation_transform,
    training_transform,
)

ROOT = Path(__file__).resolve().parent
DEFAULT_DATA_DIR = ROOT / "training_photos"
DEFAULT_OUTPUT_DIR = ROOT / "artifacts"
EXPECTED_CLASSES = {"organic", "paper", "plastic"}
TARGET_ACCURACY = 0.80
RANDOM_SEED = 42


def _set_seed(seed: int) -> None:
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    if torch.cuda.is_available():
        torch.cuda.manual_seed_all(seed)
    torch.backends.cudnn.deterministic = True
    torch.backends.cudnn.benchmark = False


def _unique_sample_indices(dataset: ImageFolder) -> list[int]:
    hashes: dict[str, int] = {}
    unique_indices = []
    for index, (path, label) in enumerate(dataset.samples):
        digest = hashlib.sha256(Path(path).read_bytes()).hexdigest()
        if digest in hashes:
            if dataset.samples[hashes[digest]][1] != label:
                raise ValueError(f"Duplicate image has conflicting labels: {path}")
            continue
        hashes[digest] = index
        unique_indices.append(index)
    return unique_indices


def _stratified_split(
    indices: list[int], labels: list[int]
) -> tuple[list[int], list[int], list[int]]:
    selected_labels = [labels[index] for index in indices]
    train_validation, test_indices = train_test_split(
        indices,
        test_size=0.15,
        random_state=RANDOM_SEED,
        stratify=selected_labels,
    )
    train_labels = [labels[index] for index in train_validation]
    train_indices, validation_indices = train_test_split(
        train_validation,
        test_size=0.1765,
        random_state=RANDOM_SEED,
        stratify=train_labels,
    )
    return train_indices, validation_indices, test_indices


def _run_epoch(
    model: nn.Module,
    loader: DataLoader[Any],
    criterion: nn.Module,
    device: torch.device,
    optimizer: torch.optim.Optimizer | None = None,
) -> tuple[float, float]:
    training = optimizer is not None
    model.train(training)
    total_loss = 0.0
    total_correct = 0
    total_samples = 0

    for images, labels in loader:
        images = images.to(device)
        labels = labels.to(device)
        if training:
            optimizer.zero_grad(set_to_none=True)

        with torch.set_grad_enabled(training):
            logits = model(images)
            loss = criterion(logits, labels)
            if training:
                loss.backward()
                optimizer.step()

        batch_size = labels.size(0)
        total_loss += loss.item() * batch_size
        total_correct += (logits.argmax(dim=1) == labels).sum().item()
        total_samples += batch_size

    if total_samples == 0:
        raise ValueError("A training or validation split is empty.")
    return total_loss / total_samples, total_correct / total_samples


def _predict(
    model: nn.Module, loader: DataLoader[Any], device: torch.device
) -> tuple[list[int], list[int], list[float], list[bool]]:
    model.eval()
    expected: list[int] = []
    predicted: list[int] = []
    confidences: list[float] = []
    view_agreements: list[bool] = []
    with torch.inference_mode():
        for images, labels in loader:
            images = images.to(device)
            original_probabilities = torch.softmax(model(images), dim=1)
            flipped_probabilities = torch.softmax(model(images.flip([-1])), dim=1)
            probabilities = (original_probabilities + flipped_probabilities) / 2
            expected.extend(labels.tolist())
            predicted.extend(probabilities.argmax(dim=1).cpu().tolist())
            confidences.extend(probabilities.max(dim=1).values.cpu().tolist())
            view_agreements.extend(
                (original_probabilities.argmax(dim=1) == flipped_probabilities.argmax(dim=1))
                .cpu()
                .tolist()
            )
    return expected, predicted, confidences, view_agreements


def _write_json(path: Path, contents: Any) -> None:
    path.write_text(
        json.dumps(contents, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--data-dir", type=Path, default=DEFAULT_DATA_DIR)
    parser.add_argument("--output-dir", type=Path, default=DEFAULT_OUTPUT_DIR)
    parser.add_argument("--epochs", type=int, default=25)
    parser.add_argument("--batch-size", type=int, default=16)
    parser.add_argument("--patience", type=int, default=6)
    args = parser.parse_args()
    if args.epochs < 1 or args.batch_size < 1 or args.patience < 1:
        parser.error("epochs, batch-size, and patience must be positive.")
    if not args.data_dir.is_dir():
        raise FileNotFoundError(f"Training image directory not found: {args.data_dir}")

    _set_seed(RANDOM_SEED)
    torch.set_num_threads(max(1, min(4, os.cpu_count() or 1)))
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

    train_dataset = ImageFolder(args.data_dir, transform=training_transform())
    evaluation_dataset = ImageFolder(
        args.data_dir, transform=evaluation_transform()
    )
    classes = train_dataset.classes
    if set(classes) != EXPECTED_CLASSES:
        raise ValueError(
            f"Expected exactly {sorted(EXPECTED_CLASSES)}, found {classes}."
        )

    unique_indices = _unique_sample_indices(train_dataset)
    labels = train_dataset.targets
    counts = {
        category: sum(labels[index] == label for index in unique_indices)
        for label, category in enumerate(classes)
    }
    if min(counts.values()) < 10:
        raise ValueError(
            f"Each class needs at least 10 unique images for stratified evaluation: {counts}"
        )
    if len(unique_indices) < 150:
        raise ValueError(
            f"At least 150 unique photos are required; found {len(unique_indices)}."
        )

    train_indices, validation_indices, test_indices = _stratified_split(
        unique_indices, labels
    )
    generator = torch.Generator().manual_seed(RANDOM_SEED)
    train_loader = DataLoader(
        Subset(train_dataset, train_indices),
        batch_size=args.batch_size,
        shuffle=True,
        num_workers=0,
        generator=generator,
    )
    validation_loader = DataLoader(
        Subset(evaluation_dataset, validation_indices),
        batch_size=args.batch_size,
        shuffle=False,
        num_workers=0,
    )
    test_loader = DataLoader(
        Subset(evaluation_dataset, test_indices),
        batch_size=args.batch_size,
        shuffle=False,
        num_workers=0,
    )

    print(f"Classes: {classes}")
    print(f"Unique image counts: {counts}")
    print(
        f"Split sizes: train={len(train_indices)}, "
        f"validation={len(validation_indices)}, test={len(test_indices)}"
    )
    print(f"Training device: {device}")

    model = build_model(len(classes), pretrained=True).to(device)
    criterion = nn.CrossEntropyLoss(label_smoothing=0.05)
    feature_parameters = [
        parameter for parameter in model.features.parameters() if parameter.requires_grad
    ]
    classifier_parameters = [
        parameter for parameter in model.classifier.parameters() if parameter.requires_grad
    ]
    optimizer = torch.optim.AdamW(
        [
            {"params": feature_parameters, "lr": 1.5e-4, "weight_decay": 1e-4},
            {"params": classifier_parameters, "lr": 1e-3, "weight_decay": 1e-4},
        ]
    )
    scheduler = torch.optim.lr_scheduler.CosineAnnealingLR(
        optimizer, T_max=args.epochs, eta_min=1e-6
    )

    best_score = float("-inf")
    best_loss = float("inf")
    best_accuracy = 0.0
    best_state: dict[str, torch.Tensor] | None = None
    best_epoch = 0
    history: list[dict[str, float | int]] = []

    for epoch in range(1, args.epochs + 1):
        train_loss, train_accuracy = _run_epoch(
            model, train_loader, criterion, device, optimizer
        )
        validation_loss, validation_accuracy = _run_epoch(
            model, validation_loader, criterion, device
        )
        scheduler.step()
        history.append(
            {
                "epoch": epoch,
                "train_loss": train_loss,
                "train_accuracy": train_accuracy,
                "validation_loss": validation_loss,
                "validation_accuracy": validation_accuracy,
            }
        )
        print(
            f"Epoch {epoch:02d}/{args.epochs}: "
            f"train_loss={train_loss:.4f}, train_accuracy={train_accuracy:.3f}, "
            f"val_loss={validation_loss:.4f}, val_accuracy={validation_accuracy:.3f}"
        )

        if epoch >= 3 and train_accuracy >= 0.85:
            score = validation_accuracy - 0.2 * validation_loss
            if score > best_score:
                best_score = score
                best_accuracy = validation_accuracy
                best_loss = validation_loss
                best_epoch = epoch
                best_state = {
                    name: value.detach().cpu().clone()
                    for name, value in model.state_dict().items()
                }

    if best_state is None:
        raise RuntimeError("Training completed without a valid model checkpoint.")

    model.load_state_dict(best_state)
    model.to(device)
    args.output_dir.mkdir(parents=True, exist_ok=True)
    checkpoint_path = args.output_dir / "model.pt"
    torch.save(
        {
            "architecture": "mobilenet_v3_small",
            "classes": classes,
            "state_dict": best_state,
            "image_size": 224,
            "normalization_mean": [0.485, 0.456, 0.406],
            "normalization_std": [0.229, 0.224, 0.225],
            "seed": RANDOM_SEED,
        },
        checkpoint_path,
    )

    expected, predicted, confidences, view_agreements = _predict(
        model, test_loader, device
    )
    accuracy = float(accuracy_score(expected, predicted))
    accepted = [
        confidence >= MIN_CONFIDENCE and agrees
        for confidence, agrees in zip(confidences, view_agreements)
    ]
    accepted_count = sum(accepted)
    selective_accuracy = (
        sum(
            is_accepted and actual == prediction
            for is_accepted, actual, prediction in zip(accepted, expected, predicted)
        )
        / accepted_count
        if accepted_count
        else None
    )
    report = classification_report(
        expected,
        predicted,
        labels=list(range(len(classes))),
        target_names=classes,
        output_dict=True,
        zero_division=0,
    )
    matrix = confusion_matrix(
        expected, predicted, labels=list(range(len(classes)))
    )
    evaluation = {
        "source_dataset": "NeoAivara/Waste_Classification_data",
        "source_revision": "d58fa9b9187dfd63b726f6f955e2414c0c7fda4d",
        "source_license": "Apache-2.0",
        "classes": classes,
        "unique_images_per_class": counts,
        "split_sizes": {
            "train": len(train_indices),
            "validation": len(validation_indices),
            "test": len(test_indices),
        },
        "random_seed": RANDOM_SEED,
        "best_epoch": best_epoch,
        "target_accuracy": TARGET_ACCURACY,
        "target_accuracy_met": accuracy >= TARGET_ACCURACY,
        "test_accuracy": accuracy,
        "selective_classification": {
            "minimum_confidence": MIN_CONFIDENCE,
            "requires_horizontal_flip_agreement": True,
            "accepted": accepted_count,
            "coverage": accepted_count / len(expected),
            "accuracy_when_accepted": selective_accuracy,
        },
        "classification_report": report,
        "confusion_matrix": matrix.tolist(),
    }
    _write_json(args.output_dir / "evaluation.json", evaluation)
    _write_json(args.output_dir / "training_history.json", history)

    split_rows = []
    for index, actual, prediction, confidence, is_accepted in zip(
        test_indices, expected, predicted, confidences, accepted
    ):
        split_rows.append(
            {
                "image": Path(train_dataset.samples[index][0])
                .relative_to(args.data_dir)
                .as_posix(),
                "actual": classes[actual],
                "predicted": classes[prediction],
                "confidence": round(confidence, 4),
                "accepted": is_accepted,
            }
        )
    with (args.output_dir / "test_predictions.csv").open(
        "w", encoding="utf-8", newline=""
    ) as file:
        writer = csv.DictWriter(
            file,
            fieldnames=("image", "actual", "predicted", "confidence", "accepted"),
        )
        writer.writeheader()
        writer.writerows(split_rows)

    figure, axis = plt.subplots(figsize=(6, 5))
    axis.imshow(matrix, interpolation="nearest", cmap="Greens")
    axis.set(
        xticks=np.arange(len(classes)),
        yticks=np.arange(len(classes)),
        xticklabels=classes,
        yticklabels=classes,
        xlabel="Predicted label",
        ylabel="True label",
        title=f"Test confusion matrix ({accuracy:.1%} accuracy)",
    )
    plt.setp(axis.get_xticklabels(), rotation=25, ha="right", rotation_mode="anchor")
    threshold = matrix.max() / 2 if matrix.size else 0
    for row in range(matrix.shape[0]):
        for column in range(matrix.shape[1]):
            axis.text(
                column,
                row,
                str(matrix[row, column]),
                ha="center",
                va="center",
                color="white" if matrix[row, column] > threshold else "black",
            )
    figure.tight_layout()
    figure.savefig(args.output_dir / "confusion_matrix.png", dpi=180)
    plt.close(figure)

    print(f"Best epoch: {best_epoch}")
    print(f"Test accuracy: {accuracy:.2%}")
    print(f"80% target met: {'yes' if accuracy >= TARGET_ACCURACY else 'no'}")
    print(f"Model and evaluation artifacts saved to {args.output_dir}")


if __name__ == "__main__":
    main()
