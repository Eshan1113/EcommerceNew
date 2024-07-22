<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\DiscountCoupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingCharge;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
  public function addToCart(Request $request)
  {
    $product = Product::with('product_images')->find($request->id);

    if ($product == null) {
      return response()->json([
        'status' => false,
        'message' => 'Product not found'
      ]);
    }

    if (Cart::count() > 0) {
      $cartContent = Cart::content();
      $productAlreadyExist = false;

      foreach ($cartContent as $item) {
        if ($item->id == $product->id) {
          $productAlreadyExist = true;
        }
      }

      if (!$productAlreadyExist) {
        Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => $product->product_images->first() ?? '']);
        $status = true;
        $message = '<strong>' . $product->title . '</strong> added to your cart successfully.';
        session()->flash('success', $message);
      } else {
        $status = false;
        $message = $product->title . ' is already in your cart';
      }
    } else {
      Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => $product->product_images->first() ?? '']);
      $status = true;
      $message = '<strong>' . $product->title . '</strong> added to your cart successfully.';
      session()->flash('success', $message);
    }

    return response()->json([
      'status' => $status,
      'message' => $message
    ]);
  }

  public function cart()
  {
    $cartContent = Cart::content();
    $data['cartContent'] = $cartContent;
    return view('front.cart', $data);
  }

  public function updateCart(Request $request)
  {
    $rowId = $request->rowId;
    $qty = $request->qty;
    $itemInfo = Cart::get($rowId);
    $product = Product::find($itemInfo->id);

    if ($product->track_qty == 'Yes') {
      if ($qty <= $product->qty) {
        Cart::update($rowId, $qty);
        $message = 'Cart updated successfully';
        $status = true;
        session()->flash('success', $message);
      } else {
        $message = 'Requested qty(' . $qty . ') not available in stock.';
        $status = false;
        session()->flash('error', $message);
      }
    } else {
      Cart::update($rowId, $qty);
      $message = 'Cart updated successfully';
      $status = true;
      session()->flash('success', $message);
    }

    return response()->json([
      'status' => $status,
      'message' => $message
    ]);
  }

  public function deleteItem(Request $request)
  {
    $itemInfo = Cart::get($request->rowId);
    if ($itemInfo == null) {
      $errorMessage = 'Item not found in cart';
      session()->flash('error', $errorMessage);
      return response()->json([
        'status' => false,
        'message' => $errorMessage
      ]);
    }

    Cart::remove($request->rowId);
    $message = 'Item removed from cart successfully';
    session()->flash('error', $message);
    return response()->json([
      'status' => true,
      'message' => $message
    ]);
  }

  public function checkout()
  {
    $discount = 0;
    if (Cart::count() == 0) {
      return redirect()->route('front.cart');
    }

    if (!Auth::check()) {
      if (!session()->has('url.intended')) {
        session(['url.intended' => url()->current()]);
      }
      return redirect()->route('account.login');
    }

    $customerAddress = CustomerAddress::where('user_id', Auth::user()->id)->first();
    session()->forget('url.intended');
    $countries = Country::orderBy('name', 'ASC')->get();
    $totalQty = 0;
    foreach (Cart::content() as $item) {
      $totalQty += $item->qty;
    }

    $totalShippingCharge = 0;

    if ($customerAddress !== null) {
      $userCountry = $customerAddress->country_id;
      $shippingInfo = ShippingCharge::where('country_id', $userCountry)->first();

      if ($shippingInfo) {
        $totalShippingCharge = $totalQty * $shippingInfo->amount;
      }

      $grandTotal = Cart::subtotal(2, '.', '') + $totalShippingCharge;

      return view('front.checkout', [
        'countries' => $countries,
        'customerAddress' => $customerAddress,
        'totalShippingCharge' => $totalShippingCharge,
        'discount' => $discount,
        'grandTotal' => $grandTotal
      ]);
    } else {
      $grandTotal = Cart::subtotal(2, '.', '');

      return view('front.checkout', [
        'countries' => $countries,
        'customerAddress' => null,
        'totalShippingCharge' => 0,
        'discount' => $discount,
        'grandTotal' => $grandTotal
      ]);
    }
  }
  public function processCheckout(Request $request)
  {
      // ... validation and other code ...
  
      $user = Auth::user();
      CustomerAddress::updateOrCreate(
          ['user_id' => $user->id],
          [
              'first_name' => $request->first_name,
              'last_name' => $request->last_name,
              'email' => $request->email,
              'mobile' => $request->mobile,
              'country_id' => $request->country,
              'address' => $request->address,
              'apartment' => $request->apartment,
              'city' => $request->city,
              'state' => $request->state,
              'zip' => $request->zip,
          ]
      );
  
      $totalQty = 0;
      foreach (Cart::content() as $item) {
          $totalQty += $item->qty;
      }
  
      $shippingCharge = 0;
      $shippingInfo = ShippingCharge::where('country_id', $request->country)->first();
  
      if ($shippingInfo) {
          $shippingCharge = $totalQty * $shippingInfo->amount;
      }
  
      $coupon = session()->get('coupon');
      $discount = 0;
      $couponCode = null;
      $discountString = '';
  
      if ($coupon) {
          $couponCode = $coupon->code;
          if ($coupon->type == 'percent') {
              $discount = ($coupon->discount_amount / 100) * Cart::subtotal();
          } else {
              $discount = $coupon->discount_amount;
          }
          $discountString = '<div class="mt-4" id="discount-response">
              <strong>' . $coupon->code . '</strong>
              <a class="btn btn-sm btn-danger" id="remove-discount"><i class="fa fa-times"></i></a>
            </div>';
      }
  
      $subTotal = Cart::subtotal(2, '.', '');
      $grandTotal = ($subTotal - $discount) + $shippingCharge;
      $status = 'pending';
  
      $order = new Order;
      $order->subtotal = $subTotal;
      $order->shipping = $shippingCharge;
      $order->grand_total = $grandTotal;
      $order->user_id = $user->id;
      $order->first_name = $request->first_name;
      $order->last_name = $request->last_name;
      $order->email = $request->email;
      $order->mobile = $request->mobile;
      $order->address = $request->address;
      $order->apartment = $request->apartment;
      $order->city = $request->city;
      $order->state = $request->state;
      $order->zip = $request->zip;
      $order->notes = $request->order_notes;
      $order->country_id = $request->country;
      $order->coupon_code = $couponCode;
      $order->discount = $discount;
      $order->payment_status = 'unpaid';
      $order->status = 'pending';
  
      $order->save();
  
      foreach (Cart::content() as $item) {
          $orderItem = new OrderItem;
          $orderItem->product_id = $item->id;
          $orderItem->order_id = $order->id;
          $orderItem->name = $item->name;
          $orderItem->qty = $item->qty;
          $orderItem->price = $item->price;
          $orderItem->total = $item->price * $item->qty;
          $orderItem->save();
  
          // Update product stock
          $productData = Product::find($item->id);
  
          if ($productData->track_qty == 'Yes') {
              $currentQty = $productData->qty;
              $updatedQty = $currentQty - $item->qty;
              $productData->qty = $updatedQty;
              $productData->save();
          }
      }
  
      // Set order email
      orderEmail($order->id, 'customer');
  
      Cart::destroy();
  
      session()->flash('success', 'You have successfully placed your order.');
      return response()->json([
          'message' => 'Order saved successfully.',
          'orderId' => $order->id,
          'status' => true
      ]);
  }
  

  public function thankyou($id)
  {
    return view('front.thanks', [
      'id' => $id
    ]);
  }

  public function getOrderSummery(Request $request)
  {
    $subTotal = Cart::subtotal();
    $discount = 0;
    $discountString = '';

    if (session()->has('coupon')) {
      $coupon = session()->get('coupon');

      if ($coupon->type == 'percent') {
        $discount = ($coupon->discount_amount / 100) * $subTotal;
      } else {
        $discount = $coupon->discount_amount;
      }

      $discountString = '<div class="mt-4" id="discount-response">
          <strong>' . $coupon->code . '</strong>
          <a class="btn btn-sm btn-danger" id="remove-discount"><i class="fa fa-times"></i></a>
        </div>';
    }

    $shippingCharge = 0;
    $countryId = $request->country_id ?? null;

    if ($countryId) {
      $shippingInfo = ShippingCharge::where('country_id', $countryId)->first();

      if ($shippingInfo) {
        $totalQty = 0;
        foreach (Cart::content() as $item) {
          $totalQty += $item->qty;
        }
        $shippingCharge = $totalQty * $shippingInfo->amount;
      }
    }

    $grandTotal = ($subTotal - $discount) + $shippingCharge;

    return response()->json([
      'status' => true,
      'subTotal' => number_format($subTotal, 2),
      'discount' => number_format($discount, 2),
      'discountString' => $discountString,
      'shippingCharge' => number_format($shippingCharge, 2),
      'grandTotal' => number_format($grandTotal, 2),
    ]);
  }

  public function applyDiscount(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'code' => 'required|string|exists:discount_coupons,code'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Invalid discount coupon code',
        'errors' => $validator->errors()
      ]);
    }

    $coupon = DiscountCoupon::where('code', $request->code)->first();

    if (!$coupon) {
      return response()->json([
        'status' => false,
        'message' => 'Invalid discount coupon code',
      ]);
    }

    //max uses check
    if($coupon->max_uses > 0){

      $couponUsed=Order::where('coupon_code',$coupon->code)->count();
      if($couponUsed >= $coupon->max_uses){
        return response()->json([
          'status' => false,
          'message' => 'Invalid discount coupon code',
        ]);
     }
    }
   


   //max uses user check
   if($coupon->max_uses_user > 0){

    $couponUsedByUser =Order::where(['coupon_code'=> $coupon->code, 'user_id' => Auth::user()->id])->count();
    if( $couponUsedByUser >= $coupon->max_uses_user){
      return response()->json([
       'status' => false,
        'message' => 'You have already used this coupon code.',
      ]);
    }
   }
   


    $subTotal = Cart::subtotal();
    //min amount condition check
    if($coupon->min_amount > 0){

      if($subTotal  < $coupon->min_amount){

        return response()->json([
          'status' => false,
           'message' => 'your minimum amount must be $'.$coupon->min_amount.'.',
         ]);
      }
    }

    session()->put('coupon', $coupon);

    $request->merge(['country' => $request->country_id]);

    return $this->getOrderSummery($request);
  }

  public function removeCoupon(Request $request)
  {
    session()->forget('coupon');
    return $this->getOrderSummery($request);
  }
}
