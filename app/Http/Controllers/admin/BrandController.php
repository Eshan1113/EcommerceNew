<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function create()
    {
        return view('admin.brands.create');
    }

    public function index(Request $request)
    {
        $query = Brand::query();

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $brands = $query->latest('id')->paginate(10);
        return view('admin.brands.list', compact('brands'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:brands',
           
            'status' => 'required'
        ]);
    
        if ($validator->passes()) {
            $brand = new Brand();
            $brand->name = $request->name;
            $brand->slug = $request->slug;
            $brand->status = $request->status;
          
            $brand->save();
    
            $request->session()->flash('success', 'Brand Created successfully.');
    
            return response()->json([
                'status' => true,
                'message' => 'Brand added successfully'
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
        $brand = Brand::find($id);
        if (empty($brand)) {
            $request->session()->flash('error', 'Record Not Found');
            return redirect()->route('brands.index');
        }

        $data['brand'] = $brand;
        return view('admin.brands.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);
        if (empty($brand)) {
            $request->session()->flash('error', 'Record Not Found');
            return response()->json([
                'status' => false,
                'notFound' => true
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $brand->id,
        ]);

        if ($validator->passes()) {
            $brand->name = $request->name;
            $brand->slug = $request->slug;
            $brand->status = $request->status;
            $brand->save();
            $request->session()->flash('success','Sub Category Update successfully.'); 
            return response()->json([
                'status' => true,
                'message' => 'Brand updated successfully'
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
        $brand =Brand :: find($id);
        if(empty($brand)) {
          $request->session()->flash('error','Record Not Found');
          return response([
            'status'=> false,
            'notFound' => true
          ]);
          //return redirect()->route('sub-categories.index');
        }
        $brand->delete();
        $request->session()->flash('success','Sub Category Deleted successfully.'); 
    
        $brand->delete();
        return response()->json([
            'status' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }
}
