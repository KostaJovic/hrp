<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'documentable_type' => ['required', Rule::in(StoreDocumentRequest::DOCUMENTABLES)],
            'documentable_id' => ['required', 'integer'],
        ]);

        return DocumentResource::collection(
            Document::query()
                ->where('documentable_type', $filters['documentable_type'])
                ->where('documentable_id', $filters['documentable_id'])
                ->orderByDesc('created_at')
                ->get(),
        );
    }

    public function store(StoreDocumentRequest $request)
    {
        $documentable = $request->documentable();

        if ($documentable === null) {
            return response()->json(['message' => 'The referenced record does not exist.'], 422);
        }

        $file = $request->file('file');
        // Private disk on purpose: invoices and warranty documents must only
        // be reachable through the authenticated download route below.
        $path = $file->store('documents/'.$request->input('documentable_type'));

        $document = $documentable->documents()->create([
            'kind' => $request->input('kind'),
            'title' => $request->input('title'),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return new DocumentResource($document);
    }

    public function download(Document $document)
    {
        return Storage::download($document->file_path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        Storage::delete($document->file_path);
        $document->delete();

        return response()->noContent();
    }
}
