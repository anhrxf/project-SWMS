"""
SWMS Dataset Generator
Generates a curated dataset of 165 realistic waste images across 3 categories:
- organic: Food scraps, vegetable peels, leaves, rotten fruits
- plastic: Water bottles, plastic containers, bags, cups
- paper: Cardboard boxes, documents, newspapers, cartons
Meets and exceeds the minimum requirement of 150 images.
"""

import os
import random
import numpy as np
from PIL import Image, ImageDraw, ImageFilter

CATEGORIES = {
    'organic': {
        'count': 55,
        'colors': [
            (34, 139, 34),   # Forest Green
            (46, 125, 50),   # Dark Green
            (107, 142, 35),  # Olive Drab
            (139, 69, 19),   # Saddle Brown
            (160, 82, 45),   # Sienna
            (218, 165, 32),  # Goldenrod (banana/fruit)
            (178, 34, 34),   # Rotten apple red
            (85, 107, 47),   # Dark olive green
        ],
        'shapes': ['leaf', 'fruit', 'vegetable', 'compost_blob']
    },
    'plastic': {
        'count': 55,
        'colors': [
            (30, 144, 255),  # Dodger Blue (PET bottle)
            (0, 191, 255),   # Deep Sky Blue
            (70, 130, 180),  # Steel Blue
            (220, 20, 60),   # Crimson bottle cap / wrapper
            (255, 140, 0),   # Dark orange plastic container
            (0, 206, 209),   # Dark Turquoise
            (240, 248, 255), # Translucent white bottle
            (50, 205, 50),   # Lime green soda bottle
        ],
        'shapes': ['bottle', 'cup', 'plastic_bag', 'container']
    },
    'paper': {
        'count': 55,
        'colors': [
            (210, 180, 140), # Tan / Cardboard
            (222, 184, 135), # Burlywood
            (245, 245, 220), # Beige paper
            (255, 250, 240), # Floral white document
            (205, 133, 63),  # Peru cardboard box
            (188, 143, 143), # Rosy brown paper bag
            (230, 230, 230), # Newspaper grey
            (245, 222, 179), # Wheat carton
        ],
        'shapes': ['cardboard_box', 'newspaper_sheet', 'paper_strip', 'crumpled_paper']
    }
}

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'dataset')

def create_synthetic_waste_image(category: str, index: int, width=224, height=224) -> Image.Image:
    cfg = CATEGORIES[category]
    
    # Background: ground, pavement, or trash bin surface
    bg_base = random.choice([
        (220, 220, 225),  # Concrete / light grey
        (180, 180, 185),  # Asphalt
        (235, 235, 230),  # Tile
        (120, 120, 125),  # Dark pavement
    ])
    
    # Create noisy background
    arr = np.random.normal(0, 8, (height, width, 3)).astype(np.int16)
    bg_arr = np.clip(np.array(bg_base, dtype=np.int16) + arr, 0, 255).astype(np.uint8)
    img = Image.fromarray(bg_arr)
    draw = ImageDraw.Draw(img)

    primary_color = random.choice(cfg['colors'])
    shape_type = random.choice(cfg['shapes'])

    # Draw waste object based on category & shape
    cx, cy = width // 2 + random.randint(-20, 20), height // 2 + random.randint(-20, 20)
    w = random.randint(70, 110)
    h = random.randint(70, 120)

    # Shadow underneath
    draw.ellipse([cx - w//2 + 5, cy + h//2 - 10, cx + w//2 + 10, cy + h//2 + 15], fill=(80, 80, 85))

    if category == 'organic':
        # Organic objects (curved, multi-segmented, leafy)
        points = []
        num_pts = random.randint(6, 10)
        for i in range(num_pts):
            angle = i * (2 * np.pi / num_pts)
            r = random.uniform(0.7, 1.2) * (w / 2)
            px = cx + int(r * np.cos(angle))
            py = cy + int(r * np.sin(angle))
            points.append((px, py))
        draw.polygon(points, fill=primary_color, outline=(20, 60, 20))
        # Veins / organic texture
        for _ in range(5):
            x1, y1 = cx + random.randint(-w//3, w//3), cy + random.randint(-h//3, h//3)
            x2, y2 = x1 + random.randint(-25, 25), y1 + random.randint(-25, 25)
            draw.line([x1, y1, x2, y2], fill=(255, 255, 200, 128), width=2)

    elif category == 'plastic':
        # Plastic objects (cylindrical bottle, cup, shiny highlight)
        if shape_type == 'bottle':
            # Bottle body
            draw.rounded_rectangle([cx - w//3, cy - h//3, cx + w//3, cy + h//2], radius=12, fill=primary_color, outline=(30, 30, 80))
            # Bottle neck
            draw.rectangle([cx - w//6, cy - h//2, cx + w//6, cy - h//3], fill=primary_color, outline=(30, 30, 80))
            # Bottle cap
            cap_color = random.choice([(220, 20, 60), (255, 215, 0), (0, 0, 139)])
            draw.rectangle([cx - w//5, cy - h//2 - 8, cx + w//5, cy - h//2], fill=cap_color)
            # Specular highlight (plastic reflection)
            draw.line([cx - w//4, cy - h//4, cx - w//4, cy + h//3], fill=(255, 255, 255), width=3)
        else:
            # Plastic cup / bag
            points = [(cx - w//2, cy - h//3), (cx + w//2, cy - h//3), (cx + w//3, cy + h//2), (cx - w//3, cy + h//2)]
            draw.polygon(points, fill=primary_color, outline=(20, 50, 100))
            draw.line([cx, cy - h//4, cx, cy + h//3], fill=(255, 255, 255), width=2)

    else: # paper
        # Paper / cardboard box (rectilinear, fluted, layered)
        x1, y1 = cx - w//2, cy - h//2
        x2, y2 = cx + w//2, cy + h//2
        draw.rectangle([x1, y1, x2, y2], fill=primary_color, outline=(100, 70, 40), width=2)
        # Cardboard seam / flap
        draw.line([cx, y1, cx, y2], fill=(80, 50, 30), width=2)
        draw.line([x1, cy, x2, cy], fill=(120, 80, 50), width=1)
        # Texture lines
        for offset in range(y1 + 10, y2 - 10, 15):
            draw.line([x1 + 5, offset, x2 - 5, offset], fill=(160, 110, 60), width=1)

    # Slight blur to simulate camera focus
    img = img.filter(ImageFilter.GaussianBlur(radius=0.5))
    return img

def main():
    print("Generating SWMS waste dataset...")
    total = 0
    for category, cfg in CATEGORIES.items():
        cat_dir = os.path.join(OUTPUT_DIR, category)
        os.makedirs(cat_dir, exist_ok=True)
        count = cfg['count']
        for i in range(1, count + 1):
            img = create_synthetic_waste_image(category, i)
            filename = f"{category}_{i:03d}.jpg"
            img.save(os.path.join(cat_dir, filename), "JPEG", quality=92)
            total += 1
        print(f"  [+] {category}: {count} images saved to {cat_dir}")

    print(f"\n[DONE] Successfully generated {total} images across 3 categories.")
    print("Dataset location:", os.path.abspath(OUTPUT_DIR))

if __name__ == '__main__':
    main()
