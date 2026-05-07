#!/usr/bin/env python3
import fitz  # PyMuPDF
import json
import re
import sys
from pathlib import Path
from collections import defaultdict

if len(sys.argv) < 2:
    print("Usage: extract_pdf_fields.py file.pdf", file=sys.stderr)
    sys.exit(1)

pdf_path = Path(sys.argv[1])

ALIGN_MAP = {0: "left", 1: "center", 2: "right"}

def detect_bold(font_name):
    if not font_name:
        return False
    lower = font_name.lower()
    return any(token in lower for token in ["bold", ",bd", "-bd", "black", "heavy"])

def infer_data_type(script_fmt):
    if not script_fmt:
        return "string"
    if "AFNumber_Format" in script_fmt or "AFNumber_Keystroke" in script_fmt:
        if re.search(r'["\'][\$€£¥]["\']', script_fmt):
            return "currency"
        return "number"
    if "AFDate_FormatEx" in script_fmt or "AFDate_Format" in script_fmt:
        return "date"
    if "AFPercent_Format" in script_fmt:
        return "percentage"
    return "string"

doc = fitz.open(pdf_path)
results = []
name_counts = defaultdict(int)

for page_index, page in enumerate(doc):
    widgets = page.widgets()
    if not widgets:
        continue

    for w in widgets:
        rect = w.rect
        name_counts[w.field_name] += 1
        css_id = f"{w.field_name}#{name_counts[w.field_name]}"
        field_name = w.field_name if name_counts[w.field_name] == 1 else css_id

        results.append({
            "name": field_name,
            "type": w.field_type_string,
            "value": w.field_value or "",
            "page": page_index + 1,
            "css": {
                "left": round(rect.x0, 2),
                "top": round(rect.y0, 2),
                "width": round(rect.width, 2),
                "height": round(rect.height, 2),
                "font": w.text_font,
                "font-size": w.text_fontsize,
                "font-weight": "bold" if detect_bold(w.text_font) else "normal",
                "text-align": ALIGN_MAP.get(getattr(w, 'text_align', 0), "left"),
                "text-color": w.text_color,
                "background-color": w.fill_color,
                "border-color": w.border_color,
                "border-style": w.border_style,
                "border-width": w.border_width,
                "data-type": infer_data_type(getattr(w, 'script_format', None)),
            }
        })

print(json.dumps({
    "file": pdf_path.name,
    "page_count": doc.page_count,
    "fields": results
}, indent=2))
