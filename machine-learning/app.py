"""Run the FastAPI waste-classification service."""

import os
import uvicorn

if __name__ == "__main__":
    host = os.environ.get("HOST", "127.0.0.1" if "PORT" not in os.environ else "0.0.0.0")
    port = int(os.environ.get("PORT", 8001))
    uvicorn.run("fastapi_app:app", host=host, port=port, reload=False)
