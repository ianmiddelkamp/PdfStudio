# PDF Studio

A Laravel-based web application for working with PDF documents. Upload PDFs, inspect and reposition form fields via a drag-and-drop editor, extract page backgrounds, and generate filled PDFs from user input.

---

## Features

- **Upload & manage PDFs** — store and organise PDF documents
- **Form field extraction** — parse field names, types, positions, and styles using PyMuPDF
- **Page rasterization** — burst and rasterize PDF pages to images via ImageMagick (fields flattened before rendering)
- **Drag-and-drop field editor** — visually reposition form fields overlaid on page images
- **CSS coordinate mapping** — field positions converted from PDF point coordinates to CSS
- **PDF fill & export** — fill form fields and export a completed PDF via pdftk

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 11 |
| Frontend | Vue 3, Inertia.js, Vite |
| PDF tooling | pdftk, ImageMagick, Python 3 + PyMuPDF |
| Database | SQLite (dev) / MySQL (prod) |
| Storage | Laravel local filesystem |

---

## Requirements

### System dependencies

| Tool | Check | Notes |
|---|---|---|
| pdftk | `pdftk --version` | Bundled for Windows at `bin/pdftk.exe` |
| ImageMagick | `magick --version` | Must be installed system-wide |
| Python 3 | `python --version` | Requires PyMuPDF: `pip install pymupdf` |

### PHP & Node

- PHP 8.2+
- Composer
- Node.js 18+

---

## Setup

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Install JS dependencies

```bash
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` as needed. By default the project uses SQLite:

```env
DB_CONNECTION=sqlite
```

Tool paths can be overridden in `.env` if binaries are not on your system PATH:

```env
PDF_PDFTK_PATH=C:\custom\pdftk.exe
PDF_MAGICK_PATH=magick
PDF_PYTHON_PATH=python
```

### 4. Create the database

```bash
touch database/database.sqlite
php artisan migrate
```

### 5. Create the storage symlink

```bash
php artisan storage:link
```

### 6. Start the development servers

In two separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Visit `http://localhost:8000`.

---

## Queue Worker

PDF processing (rasterization, field extraction) runs as a background job. In development you can run jobs synchronously by setting `QUEUE_CONNECTION=sync` in `.env`, or start a worker:

```bash
php artisan queue:work
```

---

## Project Structure

```
app/
  Http/Controllers/    Route controllers
  Models/              Eloquent models
  Services/            PDF business logic
    PdfService.php       Page rasterization pipeline (pdftk + ImageMagick)
    FdfService.php       FDF generation for filling PDF forms
    EnvironmentService.php  Tool availability checks
bin/
  pdftk.exe            Bundled Windows pdftk binary
  libiconv2.dll        pdftk dependency
python/
  extract_pdf_fields.py  Field extraction via PyMuPDF
storage/
  app/pdfs/            Uploaded PDF files
  app/page-images/     Rasterized page images
  app/temp/            Temporary files during processing
```
