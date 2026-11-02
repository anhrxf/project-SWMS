"""Download a reproducible, balanced image subset from the cited HF dataset."""

from __future__ import annotations

import csv
import hashlib
import io
import random
import time
from pathlib import Path
from typing import Any

import requests
from PIL import Image, UnidentifiedImageError

ROOT = Path(__file__).resolve().parent
OUTPUT_DIR = ROOT / "training_photos"
ATTRIBUTION_FILE = OUTPUT_DIR / "attributions.csv"
DATASET = "NeoAivara/Waste_Classification_data"
REVISION = "d58fa9b9187dfd63b726f6f955e2414c0c7fda4d"
DATASET_LICENSE = "Apache-2.0"
SOURCE_API = "https://datasets-server.huggingface.co/rows"
SOURCE_PAGE = f"https://huggingface.co/datasets/{DATASET}/tree/{REVISION}"
SAMPLES_PER_CLASS = 50
BATCH_SIZE = 100
RANDOM_SEED = 42
CLASS_ROWS = {
    "organic": (1, 945, 1929),
    "paper": (7, 10151, 11200),
    "plastic": (8, 11201, 12065),
}
CSV_FIELDS = (
    "filename",
    "category",
    "source_dataset",
    "source_revision",
    "source_row",
    "source_label",
    "dataset_license",
    "source_url",
    "sha256",
)


def _get_json(
    session: requests.Session, params: dict[str, Any]
) -> dict[str, Any]:
    last_error: Exception | None = None
    for attempt in range(4):
        try:
            response = session.get(SOURCE_API, params=params, timeout=60)
            if response.status_code in {429, 503}:
                time.sleep(2**attempt)
                continue
            response.raise_for_status()
            return response.json()
        except (requests.RequestException, ValueError) as error:
            last_error = error
            time.sleep(2**attempt)
    raise RuntimeError("Hugging Face dataset rows could not be retrieved.") from last_error


def _load_class_rows(
    session: requests.Session, category: str, label_id: int, start: int, end: int
) -> list[dict[str, Any]]:
    rows = []
    for offset in range(start, end + 1, BATCH_SIZE):
        params = {
            "dataset": DATASET,
            "config": "default",
            "split": "train",
            "revision": REVISION,
            "offset": offset,
            "length": min(BATCH_SIZE, end - offset + 1),
        }
        result = _get_json(session, params)
        for item in result.get("rows", []):
            row = item["row"]
            if row["label"] != label_id:
                raise RuntimeError(
                    f"Dataset row {item['row_idx']} no longer matches "
                    f"the documented {category} class."
                )
            image = row.get("image")
            if image and image.get("src"):
                rows.append(
                    {
                        "row_id": item["row_idx"],
                        "label": label_id,
                        "image_url": image["src"],
                    }
                )
        time.sleep(0.25)
    return rows


def _existing_rows() -> list[dict[str, str]]:
    if not ATTRIBUTION_FILE.exists():
        return []
    with ATTRIBUTION_FILE.open(encoding="utf-8", newline="") as file:
        return list(csv.DictReader(file))


def _append_attribution(row: dict[str, str]) -> None:
    write_header = not ATTRIBUTION_FILE.exists()
    with ATTRIBUTION_FILE.open("a", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=CSV_FIELDS)
        if write_header:
            writer.writeheader()
        writer.writerow(row)
        file.flush()


def _save_image(
    session: requests.Session, candidate: dict[str, Any], target: Path
) -> str:
    response = session.get(candidate["image_url"], timeout=45)
    response.raise_for_status()
    with Image.open(io.BytesIO(response.content)) as source:
        if source.width < 128 or source.height < 128:
            raise ValueError("Source image resolution is too small.")
        source.convert("RGB").save(target, format="JPEG", quality=92)
    return hashlib.sha256(target.read_bytes()).hexdigest()


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    existing = _existing_rows()
    if any(row["source_revision"] != REVISION for row in existing):
        raise RuntimeError("Existing image manifest uses a different dataset revision.")

    session = requests.Session()
    session.headers.update(
        {"User-Agent": "SWMS-Waste-Classification/1.0 (educational project)"}
    )
    rng = random.Random(RANDOM_SEED)
    seen_hashes = {row["sha256"] for row in existing}

    for category, (label_id, start, end) in CLASS_ROWS.items():
        category_rows = [row for row in existing if row["category"] == category]
        if len(category_rows) >= SAMPLES_PER_CLASS:
            print(f"{category}: already has {len(category_rows)} images")
            continue

        candidates = _load_class_rows(
            session, category, label_id, start, end
        )
        rng.shuffle(candidates)
        used_row_ids = {row["source_row"] for row in existing}
        candidates = [
            row for row in candidates if str(row["row_id"]) not in used_row_ids
        ]
        category_dir = OUTPUT_DIR / category
        category_dir.mkdir(exist_ok=True)
        index = len(category_rows) + 1

        for candidate in candidates:
            if index > SAMPLES_PER_CLASS:
                break
            target = category_dir / f"{category}_{index:03d}.jpg"
            while target.exists():
                index += 1
                target = category_dir / f"{category}_{index:03d}.jpg"
            try:
                digest = _save_image(session, candidate, target)
            except (
                requests.RequestException,
                UnidentifiedImageError,
                OSError,
                ValueError,
            ) as error:
                print(f"Skipping dataset row {candidate['row_id']}: {error}")
                target.unlink(missing_ok=True)
                continue

            if digest in seen_hashes:
                target.unlink(missing_ok=True)
                continue

            row = {
                "filename": target.relative_to(OUTPUT_DIR).as_posix(),
                "category": category,
                "source_dataset": DATASET,
                "source_revision": REVISION,
                "source_row": str(candidate["row_id"]),
                "source_label": str(label_id),
                "dataset_license": DATASET_LICENSE,
                "source_url": SOURCE_PAGE,
                "sha256": digest,
            }
            _append_attribution(row)
            existing.append(row)
            seen_hashes.add(digest)
            index += 1
            time.sleep(0.1)

        print(f"{category}: {index - 1} photos")
        if index <= SAMPLES_PER_CLASS:
            raise RuntimeError(
                f"Not enough valid source images to reach {SAMPLES_PER_CLASS} "
                f"photos for {category}."
            )

    counts = {
        category: sum(row["category"] == category for row in _existing_rows())
        for category in CLASS_ROWS
    }
    if any(count < SAMPLES_PER_CLASS for count in counts.values()):
        raise RuntimeError(f"Dataset subset is incomplete: {counts}")

    readme = OUTPUT_DIR / "README.md"
    if not readme.exists():
        readme.write_text(
            f"""# Training photo subset

This balanced subset contains {SAMPLES_PER_CLASS} images each for organic,
paper, and plastic waste. The images are selected from the dataset
[`{DATASET}`]({SOURCE_PAGE}), revision `{REVISION}`.

The source dataset declares the **Apache-2.0** license. Its label mapping is
`biological` to `organic`, `paper` to `paper`, and `plastic` to `plastic`.
`attributions.csv` records the source row and SHA-256 checksum for every
included image. See the upstream dataset card and license before reusing data.
""",
            encoding="utf-8",
        )

    print(f"Balanced photo dataset and source manifest saved under {OUTPUT_DIR}.")


if __name__ == "__main__":
    main()
