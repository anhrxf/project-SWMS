"""Run the FastAPI waste-classification service on port 8001."""

import uvicorn

if __name__ == "__main__":
    uvicorn.run("fastapi_app:app", host="127.0.0.1", port=8001, reload=False)
