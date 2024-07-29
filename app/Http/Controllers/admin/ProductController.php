<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\TempImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;
use App\Http\Controllers\admin\File;
use App\Models\ProductRating;
use Illuminate\Support\Facades\File as FacadesFile; // Add this line to resolve the File facade issue

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::latest('id')->with('product_images');
        if ($request->get('keyword') != "") {
            $products = $products->where('title', 'like', '%' . $request->keyword . '%');
        }

        $products = $products->paginate();
        $data['products'] = $products;
        return view('admin.products.list', $data);
    }

    public function create()
    {
        $data = [];
        $categories = Category::orderBy('name', 'ASC')->get();
        $brands = Brand::orderBy('name', 'ASC')->get();
        $data['categories'] = $categories;
        $data['brands'] = $brands;
        return view('admin.products.create', $data);
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required',
            'slug' => 'required|unique:products',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $product = new Product;
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->shipping_returns = $request->shipping_returns;
            $product->short_description = $request->short_description;
            $product->related_products = (!empty($request->related_products)) ? implode(',',$request->related_products) : '';
            $product->save();

            // save gallery pics
            if (!empty($request->image_array)) {
                foreach ($request->image_array as $temp_image_id) {
                    $tempImageInfo = TempImage::find($temp_image_id);
                    $extArray = explode('.', $tempImageInfo->name);
                    $ext = last($extArray); // like jpg, gif, png etc

                    $productImage = new ProductImage();
                    $productImage->product_id = $product->id;
                    $productImage->image = 'NULL';
                    $productImage->save();

                    $imageName = $product->id . '-' . $productImage->id . '-' . time() . '.' . $ext;
                    $productImage->image = $imageName;
                    $productImage->save();

                    // Generate product thumbnails
                    // Large image
                    $sourcePath = public_path('/temp/' . $tempImageInfo->name);
                    $destPath = public_path('/uploads/product/large/' . $imageName);
                    $image = Image::make($sourcePath);
                    $image->resize(1400, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $image->save($destPath);

                    // Small image
                    $destPath = public_path('/uploads/product/small/' . $imageName);
                    $image = Image::make($sourcePath);
                    $image->fit(300, 300);
                    $image->save($destPath);
                }
            }

            $request->session()->flash('success', 'Product added successfully');
            return response()->json([
                'status' => true,
                'message' => 'Product added successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($id, Request $request)
    {
        $product = Product::find($id);

        if (empty($product)) {
            return redirect()->route('products.index')->with('error', 'Product not found');
        }

        // fetch product images
        $productImages = ProductImage::where('product_id', $product->id)->get();
        $subCategories = SubCategory::where('category_id', $product->category_id)->get();

        $relatedProducts=[];
        //fetch related products
        if($product->related_products != ''){

            $productArray = explode(',',$product->related_products);
            $relatedProducts =   Product::whereIn('id',$productArray)->with('product_images')->get();
        }

        $data = [];
        $categories = Category::orderBy('name', 'ASC')->get();
        $brands = Brand::orderBy('name', 'ASC')->get();
        $data['categories'] = $categories;
        $data['brands'] = $brands;
        $data['product'] = $product;
        $data['subCategories'] = $subCategories;
        $data['productImages'] = $productImages;
        $data['relatedProducts'] = $relatedProducts;
       
       
        return view('admin.products.edit', $data);
    }

    public function update($id, Request $request)
    {
        $product = Product::find($id);

        $rules = [
            'title' => 'required',
            'slug' => 'required|unique:products,slug,' . $product->id . ',id',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products,sku,' . $product->id . ',id',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->shipping_returns = $request->shipping_returns;
            $product->short_description = $request->short_description;
            $product->related_products = (!empty($request->related_products)) ? implode(',',$request->related_products) : '';
            $product->save();

            $request->session()->flash('success', 'Product updated successfully');
            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function destroy($id, Request $request)
    {
        $product = Product::find($id);
        if (empty($product)) {
            $request->session()->flash('error', 'Product Not Found');

            return response()->json([
                'status' => false,
                'notFound' => true
            ]);
        }

        $productImages = ProductImage::where('product_id', $id)->get();
        if ($productImages->isNotEmpty()) {
            foreach ($productImages as $productImage) {
                FacadesFile::delete(public_path('uploads/product/large/' . $productImage->image)); // Fix here
                FacadesFile::delete(public_path('uploads/product/small/' . $productImage->image)); // Fix here
            }
            ProductImage::where('product_id', $id)->delete();
        }

        $product->delete();
        $request->session()->flash('success', 'Product Deleted Successfully');

        return response()->json([
            'status' => true,
            'message' => 'Product Deleted Successfully'
        ]);
    }

    public function getProducts(Request $request){

        $tempProduct = [];
        if($request->term != ""){
        $products = Product::where('title','like','%'.$request->term .'%')->get();

        if($products != null){
         foreach ($products as $product){

            $tempProduct[] = array('id' => $product->id, 'text' =>$product->title);
         }
        }
        }
     return response()->json([
        'tags' => $tempProduct,
        'status' => true
     ]);
    }

    public function productRatings(Request $request)
    {
        $ratings = ProductRating::select('product_ratings.*', 'products.title as productTitle')
            ->leftJoin('products', 'products.id', '=', 'product_ratings.product_id')
            ->orderBy('product_ratings.created_at', 'DESC');
    
        if (!empty($request->get('keyword'))) {
            $keyword = $request->get('keyword');
            $ratings = $ratings->where(function ($query) use ($keyword) {
                $query->where('products.title', 'like', '%' . $keyword . '%')
                      ->orWhere('product_ratings.username', 'like', '%' . $keyword . '%');
            });
        }
    
        $ratings = $ratings->paginate(10);
    
        return view('admin.products.ratings', [
            'ratings' => $ratings
        ]);
    }
    

    public function changeRatingStatus(Request $request)
{
    $productRating = ProductRating::find($request->id);

    if ($productRating) {
        $productRating->status = $request->status;
        $productRating->save();

        session()->flash('success', 'Status changed successfully');
        return response()->json([
            'status' => true
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Rating not found'
        ]);
    }
}

    
}
