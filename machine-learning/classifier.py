import numpy as np

class SoftmaxClassifier:
    """Softmax Multi-Class Neural Classifier with Cross-Entropy Loss."""
    def __init__(self, lr=0.08, epochs=750, reg=0.005):
        self.lr = lr
        self.epochs = epochs
        self.reg = reg
        self.W = None
        self.b = None
        self.mean = None
        self.std = None

    def fit(self, X: np.ndarray, y: np.ndarray):
        # Normalize features
        self.mean = np.mean(X, axis=0)
        self.std = np.std(X, axis=0) + 1e-6
        X_norm = (X - self.mean) / self.std

        num_samples, num_features = X_norm.shape
        num_classes = len(np.unique(y))

        # Initialize weights
        np.random.seed(42)
        self.W = np.random.randn(num_features, num_classes) * 0.01
        self.b = np.zeros(num_classes)

        # One-hot encode targets
        Y = np.zeros((num_samples, num_classes))
        Y[np.arange(num_samples), y] = 1

        # Gradient Descent with Momentum
        v_W = np.zeros_like(self.W)
        v_b = np.zeros_like(self.b)
        momentum = 0.9

        for epoch in range(self.epochs):
            # Forward pass
            scores = np.dot(X_norm, self.W) + self.b
            exp_scores = np.exp(scores - np.max(scores, axis=1, keepdims=True))
            probs = exp_scores / np.sum(exp_scores, axis=1, keepdims=True)

            # Compute gradients
            dscores = (probs - Y) / num_samples
            dW = np.dot(X_norm.T, dscores) + self.reg * self.W
            db = np.sum(dscores, axis=0)

            # Update weights with momentum
            v_W = momentum * v_W - self.lr * dW
            v_b = momentum * v_b - self.lr * db
            self.W += v_W
            self.b += v_b

    def predict_proba(self, X: np.ndarray) -> np.ndarray:
        X_norm = (X - self.mean) / self.std
        scores = np.dot(X_norm, self.W) + self.b
        exp_scores = np.exp(scores - np.max(scores, axis=1, keepdims=True))
        return exp_scores / np.sum(exp_scores, axis=1, keepdims=True)

    def predict(self, X: np.ndarray) -> np.ndarray:
        probs = self.predict_proba(X)
        return np.argmax(probs, axis=1)
