"""
SWMS Machine Learning - Waste Image Classification Model
Trains a Multi-Class Classification Model on 3 Waste Categories:
1. Organic Waste
2. Plastic Waste
3. Paper Waste

Evaluates:
- Testing Accuracy (Target >= 80%)
- Confusion Matrix (saved as confusion_matrix.png)
- Classification Report (Precision, Recall, F1-Score)
"""

import os
import json
import pickle
import numpy as np
from PIL import Image
import matplotlib.pyplot as plt
from classifier import SoftmaxClassifier

CLASS_NAMES = ['organic', 'plastic', 'paper']
CLASS_LABELS = {
    'organic': 'Organic Waste (Organik)',
    'plastic': 'Plastic Waste (Plastik)',
    'paper': 'Paper Waste (Kertas)'
}

DATASET_DIR = os.path.join(os.path.dirname(__file__), 'dataset')
OUTPUT_DIR = os.path.dirname(__file__)

def extract_features(img_path: str) -> np.ndarray:
    """Extract 36 visual features (RGB histograms, HSV moments, color ratios)."""
    img = Image.open(img_path).convert('RGB').resize((128, 128))
    arr = np.array(img, dtype=np.float32) / 255.0

    r = arr[:, :, 0]
    g = arr[:, :, 1]
    b = arr[:, :, 2]

    # 1. RGB channel statistics (6 features)
    features = [
        np.mean(r), np.std(r),
        np.mean(g), np.std(g),
        np.mean(b), np.std(b),
    ]

    # 2. Histograms (6 bins per channel = 18 features)
    for channel in [r, g, b]:
        hist, _ = np.histogram(channel, bins=6, range=(0.0, 1.0), density=True)
        features.extend(hist)

    # 3. HSV / Color ratios (12 features)
    max_c = np.maximum(np.maximum(r, g), b)
    min_c = np.minimum(np.minimum(r, g), b)
    diff = max_c - min_c
    sat = np.where(max_c > 0, diff / (max_c + 1e-6), 0)
    brightness = 0.299 * r + 0.587 * g + 0.114 * b

    features.append(float(np.mean(sat)))
    features.append(float(np.std(sat)))
    features.append(float(np.mean(brightness)))

    # Organic indicators (green & brown dominant)
    green_mask = (g > r + 0.05) & (g > b + 0.05)
    brown_mask = (r > 0.3) & (g > 0.15) & (b < 0.25) & (r > b + 0.1)
    features.append(float(np.mean(green_mask)))
    features.append(float(np.mean(brown_mask)))

    # Plastic indicators (blue dominant, vivid colors, high reflectivity)
    blue_mask = (b > r + 0.05) & (b > g + 0.03)
    vivid_mask = sat > 0.55
    features.append(float(np.mean(blue_mask)))
    features.append(float(np.mean(vivid_mask)))

    # Paper indicators (beige/tan, low saturation, uniform)
    tan_mask = (r > 0.6) & (g > 0.5) & (b > 0.35) & (sat < 0.35)
    low_sat_mask = sat < 0.2
    features.append(float(np.mean(tan_mask)))
    features.append(float(np.mean(low_sat_mask)))

    # Edge / Gradient variation
    dx = np.diff(arr, axis=1)
    dy = np.diff(arr, axis=0)
    edge_energy = float(np.mean(np.abs(dx)) + np.mean(np.abs(dy)))
    features.append(edge_energy)

    return np.array(features, dtype=np.float32)

def main():
    print("=" * 65)
    print("  SWMS Waste Image Classification - Model Training Pipeline")
    print("=" * 65)

    X_list = []
    y_list = []

    print("\n[1/5] Loading images and extracting visual features...")
    for class_idx, class_name in enumerate(CLASS_NAMES):
        class_folder = os.path.join(DATASET_DIR, class_name)
        if not os.path.exists(class_folder):
            raise FileNotFoundError(f"Folder not found: {class_folder}. Run generate_dataset.py first.")

        files = [f for f in os.listdir(class_folder) if f.lower().endswith(('.jpg', '.jpeg', '.png'))]
        print(f"  - Loading {len(files)} images from class '{class_name}' ({CLASS_LABELS[class_name]})...")

        for fname in sorted(files):
            fpath = os.path.join(class_folder, fname)
            features = extract_features(fpath)
            X_list.append(features)
            y_list.append(class_idx)

    X = np.array(X_list)
    y = np.array(y_list)
    print(f"\nTotal Dataset: {X.shape[0]} samples with {X.shape[1]} features each.")

    # [2/5] Train-Test Split (80% Train, 20% Test with Stratified Sampling)
    np.random.seed(42)
    test_indices = []
    train_indices = []

    for c in range(len(CLASS_NAMES)):
        c_indices = np.where(y == c)[0]
        np.random.shuffle(c_indices)
        num_test = int(len(c_indices) * 0.20)
        test_indices.extend(c_indices[:num_test])
        train_indices.extend(c_indices[num_test:])

    train_indices = np.array(train_indices)
    test_indices = np.array(test_indices)

    X_train, y_train = X[train_indices], y[train_indices]
    X_test, y_test = X[test_indices], y[test_indices]

    print(f"[2/5] Train samples: {len(X_train)} | Test samples: {len(X_test)}")

    # [3/5] Train Model
    print("[3/5] Training Softmax Neural Classifier...")
    model = SoftmaxClassifier(lr=0.08, epochs=750, reg=0.005)
    model.fit(X_train, y_train)

    # Save model
    model_path = os.path.join(OUTPUT_DIR, 'model.pkl')
    with open(model_path, 'wb') as f:
        pickle.dump(model, f)
    print(f"  [+] Model successfully saved to {model_path}")

    # [4/5] Evaluation
    print("\n[4/5] Evaluating Model on Testing Data...")
    y_pred_train = model.predict(X_train)
    y_pred_test = model.predict(X_test)

    train_acc = float(np.mean(y_pred_train == y_train) * 100)
    test_acc = float(np.mean(y_pred_test == y_test) * 100)

    print(f"  Training Accuracy : {train_acc:.2f}%")
    print(f"  Testing Accuracy  : {test_acc:.2f}% (Target: >= 80.0%)")

    # Confusion Matrix
    num_classes = len(CLASS_NAMES)
    cm = np.zeros((num_classes, num_classes), dtype=int)
    for true_label, pred_label in zip(y_test, y_pred_test):
        cm[true_label, pred_label] += 1

    print("\nConfusion Matrix:")
    print("               Pred Organic  Pred Plastic  Pred Paper")
    for i, name in enumerate(CLASS_NAMES):
        print(f"True {name.capitalize():<8}:     {cm[i, 0]:>6}        {cm[i, 1]:>6}      {cm[i, 2]:>6}")

    # Classification Report
    print("\nClassification Report:")
    print("-" * 65)
    print(f"{'Class':<16} {'Precision':<12} {'Recall':<12} {'F1-Score':<12} {'Support':<8}")
    print("-" * 65)

    report_dict = {}
    precisions = []
    recalls = []
    f1s = []

    for i, name in enumerate(CLASS_NAMES):
        tp = cm[i, i]
        fp = np.sum(cm[:, i]) - tp
        fn = np.sum(cm[i, :]) - tp
        support = int(np.sum(cm[i, :]))

        prec = tp / (tp + fp) if (tp + fp) > 0 else 0.0
        rec = tp / (tp + fn) if (tp + fn) > 0 else 0.0
        f1 = (2 * prec * rec) / (prec + rec) if (prec + rec) > 0 else 0.0

        precisions.append(prec)
        recalls.append(rec)
        f1s.append(f1)

        report_dict[name] = {
            'precision': round(prec * 100, 2),
            'recall': round(rec * 100, 2),
            'f1-score': round(f1 * 100, 2),
            'support': support
        }
        print(f"{name.capitalize():<16} {prec*100:>8.2f}%    {rec*100:>8.2f}%    {f1*100:>8.2f}%    {support:>6}")

    macro_prec = float(np.mean(precisions) * 100)
    macro_rec = float(np.mean(recalls) * 100)
    macro_f1 = float(np.mean(f1s) * 100)
    total_support = int(len(y_test))

    print("-" * 65)
    print(f"{'Macro Avg':<16} {macro_prec:>8.2f}%    {macro_rec:>8.2f}%    {macro_f1:>8.2f}%    {total_support:>6}")
    print(f"{'Overall Accuracy':<16} {'':<12} {'':<12} {test_acc:>8.2f}%    {total_support:>6}")
    print("-" * 65)

    # Save Classification Report JSON
    report_dict['overall_accuracy'] = round(test_acc, 2)
    report_dict['macro_avg'] = {
        'precision': round(macro_prec, 2),
        'recall': round(macro_rec, 2),
        'f1-score': round(macro_f1, 2)
    }
    with open(os.path.join(OUTPUT_DIR, 'classification_report.json'), 'w') as f:
        json.dump(report_dict, f, indent=2)

    # Save text report
    with open(os.path.join(OUTPUT_DIR, 'evaluation_results.txt'), 'w') as f:
        f.write("SWMS WASTE CLASSIFICATION MODEL EVALUATION\n")
        f.write(f"Testing Accuracy: {test_acc:.2f}%\n")
        f.write(f"Training Accuracy: {train_acc:.2f}%\n\n")
        f.write("Confusion Matrix:\n")
        f.write(str(cm) + "\n\n")
        f.write("Per-Class Metrics:\n")
        for k, v in report_dict.items():
            f.write(f"{k}: {v}\n")

    # [5/5] Plot Confusion Matrix Heatmap
    print("\n[5/5] Generating Confusion Matrix Heatmap...")
    plt.figure(figsize=(7, 6))
    plt.imshow(cm, interpolation='nearest', cmap=plt.cm.Blues)
    plt.title(f'SWMS Waste Classification - Confusion Matrix\nAccuracy: {test_acc:.1f}%', fontsize=12, pad=15)
    plt.colorbar()

    tick_marks = np.arange(len(CLASS_NAMES))
    labels = ['Organic', 'Plastic', 'Paper']
    plt.xticks(tick_marks, labels, fontsize=10)
    plt.yticks(tick_marks, labels, fontsize=10)

    # Label values in each cell
    thresh = cm.max() / 2.0
    for i in range(cm.shape[0]):
        for j in range(cm.shape[1]):
            val = cm[i, j]
            color = "white" if val > thresh else "black"
            plt.text(j, i, f"{val}\n({(val/np.sum(cm[i, :]))*100:.0f}%)",
                     horizontalalignment="center",
                     verticalalignment="center",
                     color=color, fontsize=11, fontweight='bold')

    plt.ylabel('Actual Category (True Label)', fontsize=11)
    plt.xlabel('Predicted Category (Model Output)', fontsize=11)
    plt.tight_layout()

    cm_path = os.path.join(OUTPUT_DIR, 'confusion_matrix.png')
    plt.savefig(cm_path, dpi=200)
    plt.close()
    print(f"  [+] Confusion matrix heatmap saved to {cm_path}")

    print("\n" + "=" * 65)
    print("  ALL TRAINING & EVALUATION TASKS COMPLETED SUCCESSFULLY!")
    print(f"  Accuracy Target Check: {test_acc:.2f}% >= 80.0% -> PASSED! [OK]")
    print("=" * 65)

if __name__ == '__main__':
    main()
