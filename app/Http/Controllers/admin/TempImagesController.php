<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TempImage;
use Image;

class TempImagesController extends Controller
{
    public function create(Request $request)
    {
        $image = $request->file('image');

        if ($image) {
            $ext = $image->getClientOriginalExtension();
            $newName = time() . '.' . $ext;

            $tempImage = new TempImage();
            $tempImage->name = $newName;
            $tempImage->save();

            $image->move(public_path('/temp'), $newName);

            // Generate thumbnail
            $sourcePath = public_path('/temp/' . $newName);
            $destPath = public_path('/uploads/category/thumb/' . $newName);
            $image = Image::make($sourcePath);
            $image->fit(300, 300);
            $image->save($destPath);

            return response()->json([
                'status' => true,
                'image_id' => $tempImage->id,
                'ImagePath' => asset('/uploads/category/thumb/' . $newName),
                'message' => 'Image uploaded successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No image uploaded'
            ], 400);
        }
    }
}


?>