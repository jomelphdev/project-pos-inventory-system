<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageController extends Controller
{
    public function store(Request $request)
    {
        $image = $request->file('image');
        $orgId = $request->user()->organization_id;
        $allowedMimeTypes = ['jpeg', 'jpg', 'png', 'JPG', 'JPEG', 'PNG'];
        $fileExtenstion = $image->extension();
        
        if (!in_array($fileExtenstion, $allowedMimeTypes))
        {
            return response()->error('File is not an image. Please upload a file with type(.jpg, .jpeg, or .png).');
        }

        try 
        {
            $path = Storage::disk('s3')->put('images/' . $orgId, $image, 'public');
        }
        catch (Throwable $e)
        {
            return response()->error('Something went wrong while trying to upload file.');
        }
        
        return response()->json([
            'success' => true,
            'image_url' => "https://" . config('filesystems.disks.s3.bucket') . ".s3-" . config('filesystems.disks.s3.region') . ".amazonaws.com/" . $path
        ]);
    }

    public function uploadBlogImage(Request $request)
    {
        $image = $request->file('image');
        $allowedMimeTypes = ['jpeg', 'jpg', 'png', 'JPG', 'JPEG', 'PNG'];
        $fileExtenstion = $image->getClientOriginalExtension();
        
        if (!in_array($fileExtenstion, $allowedMimeTypes))
        {
            return response()->error('File is not an image. Please upload a file with type(.jpg, .jpeg, or .png).');
        }

        try 
        {
            $path = Storage::disk('s3')->put('blog/images', $image, 'public');
        }
        catch (Throwable $e)
        {
            return response()->error('Something went wrong while trying to upload file.');
        }
        
        return response()->json([
            'success' => true,
            'image_url' => "https://" . config('filesystems.disks.s3.bucket') . ".s3-" . config('filesystems.disks.s3.region') . ".amazonaws.com/" . $path
        ]);
    }
}
