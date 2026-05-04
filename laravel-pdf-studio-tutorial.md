# Laravel PDF Studio — Tutorial

A hands-on guide to building a PDF form field overlay editor with Laravel, pdftk, and vanilla JS.

You already know PHP and have experience with pdftk and FPDI, so this tutorial focuses on **Laravel-specific patterns** rather than the PDF tooling itself.

---

## What You're Building

A web application where users can:
1. Upload a PDF
2. See each page rendered as an image
3. See form fields overlaid on the correct page at the correct position
4. Drag fields around to reposition them
5. Save the updated positions back to the server

---

## Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm
- pdftk installed on your system (`pdftk --version` to verify)
- Ghostscript or Imagick (for page rasterization)

---

## Part 1 — Laravel Project Setup

### 1.1 Create the project

```bash
composer create-project laravel/laravel pdf-studio
cd pdf-studio
php artisan serve
```

Visit `http://localhost:8000` — you should see the Laravel welcome page.

### 1.2 Understand the directory structure

Before writing any code, get familiar with what matters for this project:

```
app/
  Http/
    Controllers/     ← Your controllers live here
    Requests/        ← Form validation classes
  Models/            ← Eloquent models
  Services/          ← Where you'll put PDF logic
config/
  filesystems.php    ← Storage configuration
database/
  migrations/        ← Database table definitions
resources/
  views/             ← Blade templates
routes/
  web.php            ← Your URL routes
storage/
  app/               ← Where uploaded files live
```

### 1.3 Set up the database

Laravel ships with SQLite support out of the box. Open `.env` and check:

```env
DB_CONNECTION=sqlite
```

Then create the database file:

```bash
touch database/database.sqlite
php artisan migrate
```

> **Laravel concept:** Migrations are version-controlled database schema definitions. You never manually create tables — you create migration files that describe the table, then run `php artisan migrate`. This means your schema travels with your code.

---

## Part 2 — File Uploads and Storage

### 2.1 Create the PdfDocument model and migration

```bash
php artisan make:model PdfDocument -m
```

The `-m` flag creates a migration file alongside the model. Open `database/migrations/xxxx_create_pdf_documents_table.php` and define the schema:

```php
public function up(): void
{
    Schema::create('pdf_documents', function (Blueprint $table) {
        $table->id();
        $table->string('original_name');
        $table->string('stored_path');
        $table->integer('page_count')->nullable();
        $table->timestamps();
    });
}
```

Run the migration:

```bash
php artisan migrate
```

Now open `app/Models/PdfDocument.php` and declare which fields are mass-assignable:

```php
class PdfDocument extends Model
{
    protected $fillable = [
        'original_name',
        'stored_path',
        'page_count',
    ];
}
```

> **Laravel concept:** `$fillable` is a security feature. Laravel won't let you mass-assign model attributes unless they're listed here. This prevents users from injecting unexpected fields (e.g. `is_admin`) through a form.

### 2.2 Configure storage

Open `config/filesystems.php`. The `local` disk stores files in `storage/app`. You'll use a dedicated `pdfs` folder. No config change needed yet — you'll reference it by path.

Run this once to create the symlink that makes `storage/app/public` accessible from the web:

```bash
php artisan storage:link
```

### 2.3 Create the upload controller

```bash
php artisan make:controller PdfDocumentController
```

Open `app/Http/Controllers/PdfDocumentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfDocumentController extends Controller
{
    public function index()
    {
        $documents = PdfDocument::latest()->get();
        return view('documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('pdf')->store('pdfs', 'local');

        $document = PdfDocument::create([
            'original_name' => $request->file('pdf')->getClientOriginalName(),
            'stored_path'   => $path,
        ]);

        return redirect()->route('documents.show', $document);
    }

    public function show(PdfDocument $document)
    {
        return view('documents.show', compact('document'));
    }
}
```

> **Laravel concepts in this file:**
> - `$request->validate()` — throws a validation exception automatically if rules fail. Laravel redirects back with errors, no try/catch needed.
> - `$request->file('pdf')->store()` — stores the file and returns its path relative to the disk.
> - `PdfDocument::create()` — inserts a row using mass assignment.
> - `PdfDocument $document` in `show()` — this is **route model binding**. Laravel automatically fetches the model from the database based on the `{document}` route segment.

### 2.4 Register the routes

Open `routes/web.php`:

```php
use App\Http\Controllers\PdfDocumentController;

Route::get('/', [PdfDocumentController::class, 'index'])->name('documents.index');
Route::post('/documents', [PdfDocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}', [PdfDocumentController::class, 'show'])->name('documents.show');
```

> **Laravel concept:** Named routes (the `->name()` calls) let you reference routes by name rather than URL string. When you use `route('documents.show', $document)` in a controller or Blade template, Laravel generates the correct URL automatically. If you ever change the URL, you only update it in one place.

### 2.5 Create the Blade views

Create `resources/views/documents/index.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>PDF Studio</title>
</head>
<body>

<h1>PDF Studio</h1>

<form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="pdf" accept="application/pdf">
    @error('pdf')
        <p style="color:red">{{ $message }}</p>
    @enderror
    <button type="submit">Upload</button>
</form>

<h2>Uploaded Documents</h2>
<ul>
    @foreach ($documents as $document)
        <li>
            <a href="{{ route('documents.show', $document) }}">
                {{ $document->original_name }}
            </a>
        </li>
    @empty
        <li>No documents yet.</li>
    @endforeach
</ul>

</body>
</html>
```

> **Blade concepts:**
> - `@csrf` — required in every POST form. Laravel rejects forms without this token to prevent cross-site request forgery.
> - `@error('pdf')` — displays the validation error message for the `pdf` field if one exists.
> - `@foreach / @empty` — Blade's loop directive. `@empty` renders when the collection is empty.

---

## Part 3 — Service Classes (PDF Logic)

This is where your pdftk knowledge comes in. In Laravel, you keep business logic out of controllers by using **Service classes**.

### 3.1 Create a PdfService

Create `app/Services/PdfService.php` manually:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfService
{
    /**
     * Get the absolute path to a stored PDF.
     */
    public function absolutePath(string $storedPath): string
    {
        return Storage::disk('local')->path($storedPath);
    }

    /**
     * Extract form fields using pdftk.
     * Returns an array of field data.
     */
    public function extractFormFields(string $storedPath): array
    {
        $absolutePath = $this->absolutePath($storedPath);

        $process = new Process(['pdftk', $absolutePath, 'dump_data_fields']);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        return $this->parseFieldDump($process->getOutput());
    }

    /**
     * Parse pdftk dump_data_fields output into an array.
     */
    private function parseFieldDump(string $output): array
    {
        $fields = [];
        $current = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '---') {
                if (!empty($current)) {
                    $fields[] = $current;
                    $current = [];
                }
                continue;
            }

            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $current[$key] = $value;
            }
        }

        if (!empty($current)) {
            $fields[] = $current;
        }

        return $fields;
    }

    /**
     * Rasterize a single PDF page to a JPEG using Ghostscript.
     * Returns the absolute path to the output image.
     */
    public function rasterizePage(string $storedPath, int $page, int $dpi = 150): string
    {
        $absolutePath = $this->absolutePath($storedPath);
        $outputDir    = storage_path('app/page-images');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputFile = $outputDir . '/' . md5($storedPath) . "_page{$page}.jpg";

        if (!file_exists($outputFile)) {
            $process = new Process([
                'gs',
                '-dNOPAUSE', '-dBATCH', '-dSAFER',
                '-sDEVICE=jpeg',
                "-r{$dpi}",
                "-dFirstPage={$page}",
                "-dLastPage={$page}",
                "-sOutputFile={$outputFile}",
                $absolutePath,
            ]);
            $process->run();
        }

        return $outputFile;
    }
}
```

> **Laravel concept:** Service classes aren't a built-in Laravel feature — they're a convention. You create a plain PHP class in `app/Services/` and inject it into controllers via the constructor. Laravel's **service container** resolves dependencies automatically.

### 3.2 Inject the service into the controller

Update `PdfDocumentController.php`:

```php
use App\Services\PdfService;

class PdfDocumentController extends Controller
{
    public function __construct(private PdfService $pdfService) {}

    public function show(PdfDocument $document)
    {
        $fields = $this->pdfService->extractFormFields($document->stored_path);

        return view('documents.show', compact('document', 'fields'));
    }
}
```

> **Laravel concept:** This is **constructor injection**. Laravel sees that `PdfDocumentController` needs a `PdfService`, instantiates one automatically, and passes it in. You never call `new PdfService()` yourself.

---

## Part 4 — Passing Data to JavaScript

This is how Laravel hands off server-side data to your frontend.

### 4.1 Pass field data via Blade

Create `resources/views/documents/show.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{{ $document->original_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<h1>{{ $document->original_name }}</h1>

{{-- Pass PHP data to JavaScript --}}
<script>
    window.pdfFields = @json($fields);
    window.documentId = {{ $document->id }};
</script>

<div id="pdf-editor">
    {{-- Page images and field overlays will be rendered here by JS --}}
</div>

</body>
</html>
```

> **Laravel concept:** `@json($fields)` safely encodes a PHP array as a JSON string and escapes it for safe inline use. This is the standard Laravel pattern for bootstrapping JavaScript with server data. You avoid an extra HTTP round-trip for the initial data load.

---

## Part 5 — Frontend with Vanilla JS and Interact.js

### 5.1 Set up Vite

Laravel includes Vite out of the box. Install dependencies:

```bash
npm install
npm install interactjs
```

Start the dev server alongside `php artisan serve`:

```bash
npm run dev
```

### 5.2 Build the field overlay in JavaScript

Open `resources/js/app.js`:

```javascript
import interact from 'interactjs';

// Data injected from Blade
const fields = window.pdfFields ?? [];
const documentId = window.documentId;

// Track current positions (start from parsed PDF positions)
const positions = {};

function initEditor() {
    const editor = document.getElementById('pdf-editor');
    if (!editor) return;

    fields.forEach(field => {
        const name = field.FieldName;
        if (!name) return;

        // PDF coordinates: origin is bottom-left, Y axis goes up
        // CSS coordinates: origin is top-left, Y axis goes down
        // You'll need the page height to flip Y — store it on the field or page container
        const x = parseFloat(field.FieldRect?.split(',')[0] ?? 0);
        const y = parseFloat(field.FieldRect?.split(',')[1] ?? 0);
        const w = parseFloat(field.FieldRect?.split(',')[2] ?? 100) - x;
        const h = parseFloat(field.FieldRect?.split(',')[3] ?? 20) - y;

        positions[name] = { x, y, w, h };

        const el = document.createElement('div');
        el.className = 'pdf-field';
        el.dataset.fieldName = name;
        el.textContent = name;
        el.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: ${w}px;
            height: ${h}px;
            border: 2px solid #3b82f6;
            background: rgba(59,130,246,0.1);
            cursor: move;
            font-size: 11px;
            padding: 2px;
            box-sizing: border-box;
        `;

        editor.appendChild(el);
    });

    // Make all fields draggable
    interact('.pdf-field').draggable({
        listeners: {
            move(event) {
                const target = event.target;
                const name = target.dataset.fieldName;

                // Accumulate position (interact.js gives you delta, not absolute)
                const x = (parseFloat(target.getAttribute('data-x') ?? 0)) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y') ?? 0)) + event.dy;

                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);

                positions[name].x += event.dx;
                positions[name].y += event.dy;
            }
        }
    });
}

// Save positions back to Laravel
document.getElementById('save-btn')?.addEventListener('click', async () => {
    const response = await fetch(`/documents/${documentId}/fields`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ fields: positions }),
    });

    if (response.ok) {
        alert('Positions saved!');
    }
});

document.addEventListener('DOMContentLoaded', initEditor);
```

> **Important:** Add `<meta name="csrf-token" content="{{ csrf_token() }}">` to your Blade layout's `<head>`. Laravel requires this token on all POST requests, including `fetch()` calls.

---

## Part 6 — Saving Field Positions

### 6.1 Create a migration for field positions

```bash
php artisan make:migration create_pdf_fields_table
```

```php
Schema::create('pdf_fields', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pdf_document_id')->constrained()->cascadeOnDelete();
    $table->string('field_name');
    $table->float('x');
    $table->float('y');
    $table->float('width');
    $table->float('height');
    $table->string('field_type')->nullable();
    $table->timestamps();
});
```

```bash
php artisan migrate
```

### 6.2 Create the PdfField model

```bash
php artisan make:model PdfField
```

```php
class PdfField extends Model
{
    protected $fillable = ['pdf_document_id', 'field_name', 'x', 'y', 'width', 'height', 'field_type'];
}
```

Add the relationship to `PdfDocument`:

```php
public function fields()
{
    return $this->hasMany(PdfField::class);
}
```

> **Laravel concept:** `hasMany` / `belongsTo` are **Eloquent relationships**. Once defined, you can call `$document->fields` anywhere and Laravel runs the query automatically. No joins to write.

### 6.3 Add the save endpoint

In `routes/web.php`:

```php
Route::post('/documents/{document}/fields', [PdfDocumentController::class, 'saveFields'])
     ->name('documents.fields.save');
```

In `PdfDocumentController.php`:

```php
public function saveFields(Request $request, PdfDocument $document)
{
    $request->validate([
        'fields'           => ['required', 'array'],
        'fields.*.x'       => ['required', 'numeric'],
        'fields.*.y'       => ['required', 'numeric'],
        'fields.*.w'       => ['required', 'numeric'],
        'fields.*.h'       => ['required', 'numeric'],
    ]);

    foreach ($request->input('fields') as $fieldName => $pos) {
        $document->fields()->updateOrCreate(
            ['field_name' => $fieldName],
            ['x' => $pos['x'], 'y' => $pos['y'], 'width' => $pos['w'], 'height' => $pos['h']]
        );
    }

    return response()->json(['status' => 'ok']);
}
```

> **Laravel concept:** `updateOrCreate` is an Eloquent convenience method — it runs a SELECT, then either UPDATE or INSERT in one call. This is the cleanest way to upsert records.

---

## Part 7 — Generating the CSS Stylesheet

### 7.1 Add a route

```php
Route::get('/documents/{document}/stylesheet.css', [PdfDocumentController::class, 'stylesheet'])
     ->name('documents.stylesheet');
```

### 7.2 Add the controller method

```php
public function stylesheet(PdfDocument $document)
{
    $fields = $document->fields;

    $css = $fields->map(function ($field) {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $field->field_name);
        return ".field-{$safeName} {\n"
             . "    position: absolute;\n"
             . "    left: {$field->x}px;\n"
             . "    top: {$field->y}px;\n"
             . "    width: {$field->width}px;\n"
             . "    height: {$field->height}px;\n"
             . "}";
    })->implode("\n\n");

    return response($css, 200, ['Content-Type' => 'text/css']);
}
```

> **Laravel concept:** `response()` with a custom `Content-Type` header lets you serve any content type from a route — not just HTML. Returning a `text/css` response from a named route means you can use `{{ route('documents.stylesheet', $document) }}` directly in a `<link>` tag.

---

## Part 8 — Jobs and Queues (Optional but Recommended)

Rasterizing PDF pages is slow. You don't want the user to wait. Laravel's queue system lets you push work to a background worker.

### 8.1 Create a job

```bash
php artisan make:job RasterizePdfPages
```

Open `app/Jobs/RasterizePdfPages.php`:

```php
class RasterizePdfPages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PdfDocument $document) {}

    public function handle(PdfService $pdfService): void
    {
        // Rasterize every page and store the image paths
        for ($page = 1; $page <= $this->document->page_count; $page++) {
            $pdfService->rasterizePage($this->document->stored_path, $page);
        }
    }
}
```

### 8.2 Dispatch the job from your controller

In `store()`, after saving the document:

```php
RasterizePdfPages::dispatch($document);
```

### 8.3 Run the queue worker

```bash
php artisan queue:work
```

> **Laravel concept:** Jobs implement `ShouldQueue` to signal they run asynchronously. `SerializesModels` means Eloquent models are automatically serialized/deserialized between dispatch and execution — the job re-fetches the model from the database when it runs. For development, set `QUEUE_CONNECTION=sync` in `.env` to run jobs immediately without a worker.

---

## What to Build Next

Once you have the above working, natural extensions include:

- **Merge/split:** Add `MergeController` and `SplitController` using your FPDI knowledge. The Laravel patterns are identical — validate input, call a service, return a download.
- **File downloads:** Use `Storage::download($path, $filename)` to return a file download response from any controller method.
- **Authentication:** Run `php artisan breeze:install` to scaffold login/registration in minutes and scope documents to the logged-in user with `Auth::id()`.
- **Flash messages:** After a successful upload, `return redirect()->with('success', 'PDF uploaded!')` and render `session('success')` in Blade for user feedback.

---

## Key Laravel Commands Reference

| Command | Purpose |
|---|---|
| `php artisan make:model Foo -m` | Model + migration |
| `php artisan make:controller FooController` | Controller |
| `php artisan make:job FooJob` | Queued job |
| `php artisan make:request FooRequest` | Form request (validation) |
| `php artisan migrate` | Run pending migrations |
| `php artisan migrate:rollback` | Undo last migration batch |
| `php artisan tinker` | REPL with full app context |
| `php artisan route:list` | See all registered routes |
| `php artisan queue:work` | Start the queue worker |
