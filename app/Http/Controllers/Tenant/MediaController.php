<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __invoke(string $category, string $path): BinaryFileResponse
    {
        if (! in_array($category, ['logos', 'schematics'], true)
            || Str::contains($path, ['../', '..\\'])) {
            abort(404);
        }

        $tenantUid = (string) tenancy()->tenant->tenant_uid;
        $relativePath = $tenantUid.'/'.$category.'/'.ltrim($path, '/');

        if (! Storage::disk('tenant_private')->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = Storage::disk('tenant_private')->path($relativePath);
        $mime = Storage::disk('tenant_private')->mimeType($relativePath) ?: 'application/octet-stream';

        $response = new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
