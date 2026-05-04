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
        @forelse ($documents as $document)
        <li>
            <a href="{{ route('documents.show', $document) }}">
                {{ $document->original_name }}
            </a>
        </li>
        @empty
        <li>No documents yet.</li>
        @endforelse
    </ul>

</body>

</html>