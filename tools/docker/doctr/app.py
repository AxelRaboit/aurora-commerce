"""
docTR microservice for Aurora Billing OCR.

Single endpoint: POST /extract — accepts a multipart upload (PDF or image) and
returns a structured JSON payload with text + layout per page. Consumed by
Aurora\\Module\\Billing\\Ocr\\Service\\DocTrClient.

The model is loaded lazily on first request (saves ~5s of startup) and kept
warm in process memory afterwards. Run with --workers 1 to share the loaded
weights; scale horizontally by running multiple containers if needed.
"""

from __future__ import annotations

import io
import logging
import os
from threading import Lock
from typing import Any

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse, Response

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger("doctr-service")

app = FastAPI(title="Aurora docTR microservice", version="1.0.0")

_model = None
_model_lock = Lock()


def _get_model():
    """Lazy-load the docTR ocr_predictor; thread-safe via a lock."""
    global _model
    if _model is None:
        with _model_lock:
            if _model is None:
                from doctr.models import ocr_predictor

                logger.info("Loading docTR model (first request, ~5s)...")
                _model = ocr_predictor(pretrained=True, assume_straight_pages=True)
                logger.info("docTR model ready.")
    return _model


def _document_from_bytes(payload: bytes, content_type: str | None) -> Any:
    """Build a docTR DocumentFile from raw bytes (PDF or image)."""
    from doctr.io import DocumentFile

    is_pdf = (content_type == "application/pdf") or payload[:4] == b"%PDF"
    if is_pdf:
        return DocumentFile.from_pdf(payload)
    return DocumentFile.from_images(payload)


def _serialize(result) -> dict[str, Any]:
    """Reduce docTR's nested result to the minimal payload PHP cares about."""
    pages_out: list[dict[str, Any]] = []

    for page in result.pages:
        h, w = page.dimensions  # (height, width) in pixels
        blocks_out: list[dict[str, Any]] = []
        page_words: list[str] = []

        for block in page.blocks:
            for line in block.lines:
                line_words = [w.value for w in line.words]
                if not line_words:
                    continue
                line_text = " ".join(line_words)
                page_words.append(line_text)

                # Bounding box of the line: ((xmin, ymin), (xmax, ymax)) normalized [0..1]
                (xmin, ymin), (xmax, ymax) = line.geometry
                confidences = [w.confidence for w in line.words]
                avg_conf = sum(confidences) / len(confidences) if confidences else 0.0

                blocks_out.append({
                    "text": line_text,
                    "bbox": {
                        "x": round(xmin, 4),
                        "y": round(ymin, 4),
                        "w": round(xmax - xmin, 4),
                        "h": round(ymax - ymin, 4),
                    },
                    "confidence": round(float(avg_conf), 4),
                })

        pages_out.append({
            "width": int(w),
            "height": int(h),
            "text": "\n".join(page_words),
            "blocks": blocks_out,
        })

    full_text = "\n\n".join(p["text"] for p in pages_out)
    return {"pages": pages_out, "text": full_text}


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/render")
async def render(file: UploadFile = File(...), dpi: int = 150, max_pages: int = 5) -> Response:
    """Render a PDF to a single tall PNG (all pages stacked vertically) so the
    vision model sees the whole invoice at once. For images, passthrough.

    `max_pages` caps the rendered span — anything beyond is dropped to keep the
    payload bounded. 5 pages × A4 @ 150dpi ≈ 5 MB PNG.
    """
    if file.filename is None:
        raise HTTPException(status_code=400, detail="filename required")

    payload = await file.read()
    if not payload:
        raise HTTPException(status_code=400, detail="empty upload")

    is_pdf = (file.content_type == "application/pdf") or payload[:4] == b"%PDF"
    if not is_pdf:
        return Response(content=payload, media_type=file.content_type or "image/png")

    try:
        from pdf2image import convert_from_bytes
        from PIL import Image

        pages = convert_from_bytes(payload, dpi=dpi, first_page=1, last_page=max_pages, fmt="png")
        if not pages:
            raise HTTPException(status_code=422, detail="PDF has no pages")

        if len(pages) == 1:
            buf = io.BytesIO()
            pages[0].save(buf, format="PNG")
            return Response(content=buf.getvalue(), media_type="image/png")

        # Stack vertically: width = max page width, height = sum of heights.
        width = max(p.width for p in pages)
        height = sum(p.height for p in pages)
        canvas = Image.new("RGB", (width, height), "white")
        offset = 0
        for p in pages:
            # Centre narrower pages on the canvas to avoid distortion.
            x = (width - p.width) // 2
            canvas.paste(p, (x, offset))
            offset += p.height

        buf = io.BytesIO()
        canvas.save(buf, format="PNG", optimize=True)
        return Response(content=buf.getvalue(), media_type="image/png")
    except HTTPException:
        raise
    except Exception as exc:
        logger.exception("PDF rendering failed")
        raise HTTPException(status_code=500, detail=f"PDF render failed: {exc}") from exc


@app.post("/extract")
async def extract(file: UploadFile = File(...)) -> JSONResponse:
    if file.filename is None:
        raise HTTPException(status_code=400, detail="filename required")

    payload = await file.read()
    if not payload:
        raise HTTPException(status_code=400, detail="empty upload")

    if len(payload) > 50 * 1024 * 1024:
        raise HTTPException(status_code=413, detail="file too large (max 50MB)")

    try:
        document = _document_from_bytes(payload, file.content_type)
    except Exception as exc:
        logger.exception("Failed to parse upload")
        raise HTTPException(status_code=400, detail=f"invalid document: {exc}") from exc

    try:
        model = _get_model()
        result = model(document)
    except Exception as exc:
        logger.exception("docTR inference failed")
        raise HTTPException(status_code=500, detail=f"OCR failed: {exc}") from exc

    return JSONResponse(content=_serialize(result))
