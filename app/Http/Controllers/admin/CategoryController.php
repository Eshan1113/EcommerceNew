<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\TempImage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::latest();

        if (!empty($request->get('keyword'))) {
            $categories = $categories->where('name', 'like', '%' . $request->get('keyword') . '%');
        }

        $categories = $categories->paginate(6);

        return view('admin.category.list', compact('categories'));
    }

    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:categories',
        ]);

        if ($validator->passes()) {
            $category = new Category();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->showHome= $request->showHome; 
            $category->save();

            // Save image here
            if (!empty($request->image_id)) {
                $tempImage = TempImage::find($request->image_id);
                if ($tempImage) {
                    $extArray = explode('.', $tempImage->name);
                    $ext = last($extArray);

                    $newImageName = $category->id . '.' . $ext;
                    $sPath = public_path() . '/temp/' . $tempImage->name;
                    $dPath = public_path() . '/uploads/category/' . $newImageName;

                    // Check if the source file exists
                    if (File::exists($sPath)) {
                        File::copy($sPath, $dPath);

                        // Generate image thumbnail
                        $thumbPath = public_path() . '/uploads/category/thumb/';
                        if (!File::exists($thumbPath)) {
                            File::makeDirectory($thumbPath, 0755, true);
                        }

                        $dPathThumb = $thumbPath . $newImageName;
                        try {
                            $img = Image::make($sPath);
                            $img->fit(450, 600, function ($constraint) {
                                $constraint->upsize();
                            });
                            $img->save($dPathThumb);

                            // Assign the new image name to the category and save it
                            $category->image = $newImageName;
                            $category->save();
                        } catch (\Exception $e) {
                            Log::error('Error saving image thumbnail: ' . $e->getMessage());
                        }
                    } else {
                        Log::error('Source file does not exist: ' . $sPath);
                    }
                } else {
                    Log::error('TempImage not found with ID: ' . $request->image_id);
                }
            }

            $request->session()->flash('success', 'Category added successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Category added successfully.'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($categoryId, Request $request)
    {
        $category = Category::find($categoryId);
        if (empty($category)) {
            return redirect()->route('categories.index');
        }

        return view('admin.category.edit', compact('category'));
    }

    public function update($categoryId, Request $request)
{
    // Log the incoming request data
    Log::info('Update category request received', ['categoryId' => $categoryId, 'request' => $request->all()]);

    $category = Category::find($categoryId);
    if (empty($category)) {
        $request->session()->flash('error', 'Category not found');
        return response()->json([
            'status' => false,
            'notFound' => true,
            'message' => 'Category not found'
        ]);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'slug' => 'required|unique:categories,slug,' . $category->id . ',id',
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed', ['errors' => $validator->errors()]);
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ]);
    }

    // Update category fields
    $category->name = $request->name;
    $category->slug = $request->slug;
    $category->status = $request->status;
    $category->showHome = $request->showHome;
    $category->save();
    $oldImage = $category->image;

    // Save image here
    if (!empty($request->image_id)) {
        $tempImage = TempImage::find($request->image_id);
        if ($tempImage) {
            $extArray = explode('.', $tempImage->name);
            $ext = last($extArray);

            $newImageName = $category->id . '-' . time() . '.' . $ext;
            $sPath = public_path() . '/temp/' . $tempImage->name;
            $dPath = public_path() . '/uploads/category/' . $newImageName;

            // Check if the source file exists
            if (File::exists($sPath)) {
                File::copy($sPath, $dPath);

                // Generate image thumbnail
                $thumbPath = public_path() . '/uploads/category/thumb/';
                if (!File::exists($thumbPath)) {
                    File::makeDirectory($thumbPath, 0755, true);
                }

                $dPathThumb = $thumbPath . $newImageName;
                try {
                    $img = Image::make($sPath);
                    $img->fit(450, 600, function ($constraint) {
                        $constraint->upsize();
                    });
                    $img->save($dPathThumb);

                    // Assign the new image name to the category and save it
                    $category->image = $newImageName;
                    $category->save();

                    // Delete old images
                    File::delete(public_path() . '/uploads/category/thumb/' . $oldImage);
                    File::delete(public_path() . '/uploads/category/' . $oldImage);
                } catch (\Exception $e) {
                    Log::error('Error saving image thumbnail: ' . $e->getMessage());
                }
            } else {
                Log::error('Source file does not exist: ' . $sPath);
            }
        } else {
            Log::error('TempImage not found with ID: ' . $request->image_id);
        }
    }

    $request->session()->flash('success', 'Category updated successfully.');

    return response()->json([
        'status' => true,
        'message' => 'Category updated successfully.'
    ]);
}


    public function destroy($categoryId, request $request)
    {
        $category = Category::find($categoryId);
        if (empty($category)) {
            $request->session()->flash('error', 'Category not found');
            return response()->json([
                'status' => false,
                'message' => 'Category not found.'
            ]);
        }
        File::delete(public_path() . '/uploads/category/thumb/' . $category->image);
        File::delete(public_path() . '/uploads/category/' . $category->image);
        $category->delete();

        $request->session()->flash('success', 'Category deleted successfully.');

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully.'
        ]);
    }
}
