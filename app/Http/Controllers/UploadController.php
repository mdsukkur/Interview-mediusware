<?php

namespace App\Http\Controllers;

use App\TemporaryFile;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = uniqid() . "." . strtolower($file->getClientOriginalExtension());
            $folder = uniqid() . '-' . now()->timestamp;
            $file->storeAs('products/tmp/' . $folder, $filename);

            TemporaryFile::create([
                'folder' => $folder,
                'filename' => $filename,
            ]);

            return response()->json([
                'error' => false,
                'folder' => $folder,
                'filename' => $filename,
            ], 200);
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong. Please try again!'
        ], 500);
    }
}
