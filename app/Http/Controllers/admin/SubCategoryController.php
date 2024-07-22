<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{

    public function index(Request $request)
    {
        $subCategories = SubCategory::select('sub_categories.*', 'categories.name as categoryName')
        ->latest('id')
        ->leftJoin('categories', 'sub_categories.category_id', '=', 'categories.id');
        
    

        if (!empty($request->get('keyword'))) {
            $subCategories = $subCategories->where('sub_categories.name', 'like', '%' . $request->get('keyword') . '%');
        }
        if (!empty($request->get('keyword'))) {
            $subCategories = $subCategories->where('categories.name', 'like', '%' . $request->get('keyword') . '%');
        }

        $subCategories = $subCategories->paginate(10);

        return view('admin.sub_category.list', compact('subCategories'));
    }





    public function create(){
        $categories = category::orderBy('name', 'ASC')->get();
        $data['categories'] = $categories;
        return view('admin.sub_category.create', $data);
    }

    public function store(Request $request){  // Corrected method name
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:sub_categories',
            'category' => 'required',
            'status' => 'required'
        ]);

        if($validator->passes()){
            $subCategory = new SubCategory();
            $subCategory->name = $request->name;
            $subCategory->slug = $request->slug;
            $subCategory->status = $request->status;
            $subCategory->category_id = $request->category;
            $subCategory->showHome = $request->showHome; 
            $subCategory->save();
            $request->session()->flash('success','Sub Category Created successfully.'); 

            return response([
                'status' => true,
                'message' => 'Sub Category Created successfully.'
            ]);
        } else {
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
          

        }
       
    }
    public function edit($id, Request $request){

      $subCategory =SubCategory :: find($id);
      if(empty($subCategory)) {
        $request->session()->flash('error','Record Not Found');
        return redirect()->route('sub-categories.index');
      }

      $categories = Category::orderBy('name', 'ASC')->get();
      return view('admin.sub_category.edit', compact('subCategory', 'categories'));
    }

    public function update($id, Request $request){
        $subCategory =SubCategory :: find($id);
        if(empty($subCategory)) {
          $request->session()->flash('error','Record Not Found');
          return response([
            'status'=> false,
            'notFound' => true
          ]);
          //return redirect()->route('sub-categories.index');
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
           // 'slug' => 'required|unique:sub_categories',
           'slug' => 'required|unique:categories,slug,' . $subCategory->id . ',id',
            'category' => 'required',
            'status' => 'required'
        ]);

        if($validator->passes()){
            
            $subCategory->name = $request->name;
            $subCategory->slug = $request->slug;
            $subCategory->status = $request->status;
            $subCategory->category_id = $request->category;
            $subCategory->save();
            $request->session()->flash('success','Sub Category Update successfully.'); 

            return response([
                'status' => true,
                'message' => 'Sub Category Update successfully.'
            ]);
        } else {
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);

    }
    
}


 public function destroy($id, Request $request){
    
    $subCategory =SubCategory :: find($id);
    if(empty($subCategory)) {
      $request->session()->flash('error','Record Not Found');
      return response([
        'status'=> false,
        'notFound' => true
      ]);
      //return redirect()->route('sub-categories.index');
    }
    $subCategory->delete();
    $request->session()->flash('success','Sub Category Deleted successfully.'); 

    return response([
        'status' => true,
        'message' => 'Sub Category Deleted successfully.'
    ]);

 }
}