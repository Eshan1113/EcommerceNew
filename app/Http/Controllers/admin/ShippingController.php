<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function create()
    {
        $countries = Country::get();
        $data['countries'] = $countries;
        $shippingCharges = ShippingCharge::leftJoin('countries', 'countries.id', '=', 'shipping_charges.country_id')
            ->select('shipping_charges.*', 'countries.name as country_name')
            ->get();
        $data['shippingCharges'] = $shippingCharges;
        return view('admin.shipping.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

    
        $existingShippingCharge = ShippingCharge::where('country_id', $request->country)->first();

        if ($existingShippingCharge) {
            return response()->json([
                'status' => false,
                'errors' => ['country' => ['Shipping Country Added Already']]
            ]);
        }

        $shipping = new ShippingCharge();
        $shipping->country_id = $request->country;
        $shipping->amount = $request->amount;
        $shipping->save();

        session()->flash('success', 'Shipping Added successfully');

        return response()->json([
            'status' => true,
        ]);
    }

    public function edit($id)
    {
        $shippingCharge = ShippingCharge::find($id);
        $countries = Country::get();
        $data['countries'] = $countries;
        $data['shippingCharge'] = $shippingCharge;
        return view('admin.shipping.edit', $data);
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required|numeric'
        ]);

        if ($validator->passes()) {
            $shipping = ShippingCharge::find($id);
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success', 'Shipping Update successfully');

            return response()->json([
                'status' => true,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function destroy($id)
    {
        $shipping = ShippingCharge::find($id);

        if ($shipping == null) {
            session()->flash('error', 'Shipping Charge not found');
            return response()->json([
                'status' => false
            ]);
        }

        $shipping->delete();
        session()->flash('success', 'Shipping Deleted Successfully');
        return response()->json([
            'status' => true
        ]);
    }
}
