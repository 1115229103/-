# AIStory FastAPI — Python 3.12 Slim
FROM python:3.12-slim

WORKDIR /app

COPY fastapi/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY fastapi/ .

RUN useradd -m -u 1000 aistory && chown -R aistory:aistory /app
USER aistory

EXPOSE 8001
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
