#!/usr/bin/env python3
import fitz  # PyMuPDF
import json
import sys
from pathlib import Path
from collections import defaultdict

if len(sys.argv) < 2:
    print("Usage: extract_pdf_fields.py file.pdf", file=sys.stderr)
    sys.exit(1)

pdf_path = Path(sys.argv[1])

doc = fitz.open(pdf_path)
results = []
name_counts = defaultdict(int)
for page_index, page in enumerate(doc):
    page_height = page.rect.height

    widgets = page.widgets()
    if not widgets:
        continue

    for w in widgets:
        rect = w.rect  # PDF coords (bottom-left origin)
        fill_color = w.fill_color
        border_color = w.border_color
        border_style = w.border_style
        border_width = w.border_width
        text_color = w.text_color
        text_font = w.text_font
        text_fontsize = w.text_fontsize
        # Convert to CSS coords (top-left origin)
        css_left = rect.x0
        css_top = page_height - rect.y1
        css_width = rect.width
        css_height = rect.height
 # Track duplicates
        name_counts[w.field_name] += 1
        css_id = f"{w.field_name}#{name_counts[w.field_name]}"
        field_name = w.field_name
        if name_counts[w.field_name] > 1:
            field_name = css_id          
               
        results.append({
            "name": field_name,
            "type": w.field_type_string,
            "page": page_index + 1,
            "css": {
                "left": round(css_left, 2),
                "top": round(css_top, 2),
                "width": round(css_width, 2),
                "height": round(css_height, 2),
                "font": text_font,
                "font-size": text_fontsize,
                "text-color": text_color,
                "background-color": fill_color,
                "border-color": border_color,
                "border-style": border_style,
                "border-width": border_width
            }
        })

print(json.dumps({
    "file": pdf_path.name,
    "page_count": doc.page_count,
    "fields": results
}, indent=2))
