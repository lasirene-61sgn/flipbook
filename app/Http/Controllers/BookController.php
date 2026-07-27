<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::latest()->get();
        return view('welcome', compact('books'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'patch' => 'required|file',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'fileName' => 'required|string'
        ]);

        $file = $request->file('patch');
        $chunkIndex = intval($request->input('chunkIndex'));
        $totalChunks = intval($request->input('totalChunks'));
        $cleanName = basename($request->input('fileName'));

        $tempDir = storage_path('app/chunks');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true, true);
        }

        $tempFilePath = $tempDir . '/' . $cleanName;

        $out = @fopen($tempFilePath, $chunkIndex === 0 ? "wb" : "ab");
        if (!$out) {
            return response()->json(['success' => false, 'message' => 'Failed to open temporary write stream.']);
        }

        $in = @fopen($file->getPathname(), "rb");
        if (!$in) {
            fclose($out);
            return response()->json(['success' => false, 'message' => 'Failed to read incoming chunk.']);
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        fclose($in);
        fclose($out);

        if ($chunkIndex === $totalChunks - 1) {
            $finalPath = public_path('pdfs');
            if (!File::exists($finalPath)) {
                File::makeDirectory($finalPath, 0755, true, true);
            }

            $destination = $finalPath . '/' . $cleanName;

            if (!@copy($tempFilePath, $destination)) {
                if (!File::move($tempFilePath, $destination)) {
                    return response()->json(['success' => false, 'message' => 'Failed to compile file to public directory.']);
                }
            } else {
                @unlink($tempFilePath);
            }

            $dbPath = 'pdfs/' . $cleanName;
            
            // Generate Images using Ghostscript
            $folderName = uniqid() . '_' . pathinfo($cleanName, PATHINFO_FILENAME);
            $imagesDir = public_path('books/' . $folderName);
            if (!File::exists($imagesDir)) {
                File::makeDirectory($imagesDir, 0755, true, true);
            }
            
            $gsPath = '"C:\Program Files\gs\gs10.03.1\bin\gswin64c.exe"';
            $outputPattern = $imagesDir . '/page_%d.jpg';
            
            // Convert PDF to JPGs
            $cmd = "$gsPath -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r120 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -sOutputFile=\"$outputPattern\" \"$destination\"";
            shell_exec($cmd);
            
            // Get total page count by counting generated images
            $files = glob($imagesDir . '/page_*.jpg');
            $pageCount = count($files);

            Book::create([
                'title' => $request->title ?? pathinfo($cleanName, PATHINFO_FILENAME),
                'pdf_path' => $dbPath,
                'page_count' => $pageCount > 0 ? $pageCount : null,
                'folder_path' => $pageCount > 0 ? 'books/' . $folderName : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File compiled and saved successfully!',
                'redirect' => route('books.index')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chunk processed successfully.'
        ]);
    }

    public function storeImages(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // max 5MB per image
        ]);

        $images = $request->file('images');
        
        $cleanName = 'bulk_images_' . time();
        $folderName = uniqid() . '_' . $cleanName;
        $imagesDir = public_path('books/' . $folderName);
        
        if (!File::exists($imagesDir)) {
            File::makeDirectory($imagesDir, 0755, true, true);
        }

        $pageCount = 0;
        foreach ($images as $index => $image) {
            $pageCount++;
            $fileName = 'page_' . $pageCount . '.jpg';
            
            // Convert image to JPG to match frontend expectations
            $imgStr = file_get_contents($image->getPathname());
            $gdImage = @imagecreatefromstring($imgStr);
            
            if ($gdImage !== false) {
                // Create a white background canvas in case it's a transparent PNG/GIF
                $bg = imagecreatetruecolor(imagesx($gdImage), imagesy($gdImage));
                $white = imagecolorallocate($bg, 255, 255, 255);
                imagefill($bg, 0, 0, $white);
                imagecopy($bg, $gdImage, 0, 0, 0, 0, imagesx($gdImage), imagesy($gdImage));
                
                // Save as JPG
                imagejpeg($bg, $imagesDir . '/' . $fileName, 90);
                
                imagedestroy($gdImage);
                imagedestroy($bg);
            } else {
                // Fallback: just move and rename to .jpg
                $image->move($imagesDir, $fileName);
            }
        }

        Book::create([
            'title' => $request->title ?? 'Bulk Images Flipbook',
            'pdf_path' => 'images_only',
            'page_count' => $pageCount > 0 ? $pageCount : null,
            'folder_path' => $pageCount > 0 ? 'books/' . $folderName : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded and flipbook compiled successfully!',
            'redirect' => route('books.index')
        ]);
    }

    public function show(Book $book)
    {
        return view('viewer', compact('book'));
    }

    // Optimized streaming with full CORS + ngrok support
    public function stream(Book $book, Request $request)
    {
        // Handle CORS preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, [
                'Access-Control-Allow-Origin'   => '*',
                'Access-Control-Allow-Methods'  => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers'  => 'Range, Content-Type, Accept, ngrok-skip-browser-warning',
                'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges, ETag',
                'Access-Control-Max-Age'        => '86400',
            ]);
        }

        $filePath = public_path($book->pdf_path);

        if (!file_exists($filePath)) {
            abort(404, 'PDF file not found.');
        }

        $size = filesize($filePath);
        $time = filemtime($filePath);
        $fm   = gmdate('D, d M Y H:i:s ', $time) . 'GMT';
        $etag = md5($fm . $size);

        $headers = [
            'Content-Type'                  => 'application/pdf',
            'Content-Disposition'           => 'inline; filename="' . basename($filePath) . '"',
            'Accept-Ranges'                 => 'bytes',
            'Last-Modified'                 => $fm,
            'ETag'                          => '"' . $etag . '"',
            'Cache-Control'                 => 'public, must-revalidate, max-age=3600',
            'Pragma'                        => 'public',
            'Expires'                       => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            // CORS — required for pdf.js Range requests from ngrok / cross-origin
            'Access-Control-Allow-Origin'   => '*',
            'Access-Control-Allow-Methods'  => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers'  => 'Range, Content-Type, Accept, ngrok-skip-browser-warning',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges, ETag',
        ];

        // 304 Not Modified shortcut
        $clientEtag = $request->header('If-None-Match');
        if ($clientEtag === '"' . $etag . '"' || $clientEtag === $etag) {
            return response('', 304, $headers);
        }

        // HEAD request — just return headers, no body
        if ($request->isMethod('HEAD')) {
            $headers['Content-Length'] = $size;
            return response('', 200, $headers);
        }

        // Range request (pdf.js uses this for fast partial loading)
        if ($request->hasHeader('Range')) {
            $range = $request->header('Range');
            preg_match('/bytes=(\d+)-(\d*)?/', $range, $matches);

            $start  = intval($matches[1]);
            $end    = !empty($matches[2]) ? intval($matches[2]) : $size - 1;
            $end    = min($end, $size - 1);
            $length = ($end - $start) + 1;

            $headers['Content-Length'] = $length;
            $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";

            return response()->stream(function () use ($filePath, $start, $length) {
                $stream = fopen($filePath, 'rb');
                fseek($stream, $start);
                $sent = 0;
                while ($sent < $length && !feof($stream)) {
                    $read = min(65536, $length - $sent);
                    echo fread($stream, $read);
                    $sent += $read;
                    flush();
                }
                fclose($stream);
            }, 206, $headers);
        }

        // Full file response
        $headers['Content-Length'] = $size;
        return response()->stream(function () use ($filePath) {
            $fp = fopen($filePath, 'rb');
            while (!feof($fp)) { echo fread($fp, 65536); flush(); }
            fclose($fp);
        }, 200, $headers);
    }
    
    // New endpoint to get PDF metadata without loading full document
    public function metadata(Book $book)
    {
        return response()->json([
            'title' => $book->title,
            'path' => asset($book->pdf_path),
            'stream_url' => route('books.stream', $book->id)
        ]);
    }
}