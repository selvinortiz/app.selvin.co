<?php

namespace App\Http\Controllers;

use App\Models\SecureFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SecureFileDownloadController extends Controller
{
    public function show(string $token): View
    {
        $secureFile = SecureFile::where('access_token', $token)->firstOrFail();

        if (!$secureFile->canBeDownloaded()) {
            abort(404, 'File not available');
        }

        return view('secure-files.download', [
            'secureFile' => $secureFile,
        ]);
    }

    public function download(Request $request, string $token): HttpResponse
    {
        $secureFile = SecureFile::where('access_token', $token)->firstOrFail();

        if (!$secureFile->canBeDownloaded()) {
            abort(404, 'File not available');
        }

        // Check password if required
        if ($secureFile->password) {
            $password = $request->input('password');

            if ($password !== $secureFile->password) {
                return back()->withErrors(['password' => 'Invalid password']);
            }
        }

        // Increment download count
        $secureFile->incrementDownloadCount();

        // Get file from storage
        $filePath = $secureFile->file_path;

        if (!Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        $file = Storage::get($filePath);
        $headers = [
            'Content-Type' => $secureFile->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $secureFile->filename . '"',
            'Content-Length' => $secureFile->file_size,
        ];

        return response($file, 200, $headers);
    }
}
