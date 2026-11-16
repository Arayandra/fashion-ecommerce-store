<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Shipping;
use App\User;
use PDF;
use Notification;
use Helper;
use Illuminate\Support\Str;
use App\Notifications\StatusNotification;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders=Order::orderBy('id','DESC')->paginate(10);
        return view('backend.order.index')->with('orders',$orders);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address1' => ['required', 'string', 'max:1000'],
            'address2' => ['nullable', 'string', 'max:1000'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'post_code' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'shipping' => ['nullable', 'integer', 'exists:shippings,id'],
            'payment_method' => ['nullable', 'in:cod,paypal'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'cod';
        $shippingId = $validated['shipping'] ?? null;

        try {
            $order = DB::transaction(function () use ($validated, $paymentMethod, $shippingId) {
                $cartItems = Cart::with('product')
                    ->where('user_id', auth()->id())
                    ->whereNull('order_id')
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    return null;
                }

                foreach ($cartItems as $item) {
                    if (!$item->product || (int) $item->quantity < 1) {
                        throw new \RuntimeException('A cart item is no longer available.');
                    }

                    if ((int) $item->product->stock < (int) $item->quantity) {
                        throw new \RuntimeException('Insufficient stock for ' . $item->product->title . '.');
                    }
                }

                $subtotal = $cartItems->sum(function ($item) {
                    return (float) $item->price * (int) $item->quantity;
                });
                $quantity = $cartItems->sum('quantity');

                $shippingPrice = 0;
                if ($shippingId) {
                    $shipping = Shipping::whereKey($shippingId)
                        ->where('status', 'active')
                        ->first();

                    if (!$shipping) {
                        throw new \RuntimeException('The selected shipping method is no longer available.');
                    }

                    $shippingPrice = (float) $shipping->price;
                }

                // Coupon values are kept server-side in the session; never trust
                // a coupon amount submitted in the checkout form.
                $couponDiscount = min(
                    max(0, (float) session('coupon.value', 0)),
                    $subtotal
                );

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => auth()->id(),
                    'sub_total' => $subtotal,
                    'shipping_id' => $shippingId,
                    'coupon' => $couponDiscount > 0 ? $couponDiscount : null,
                    'total_amount' => max(0, $subtotal + $shippingPrice - $couponDiscount),
                    'quantity' => $quantity,
                    'payment_method' => $paymentMethod,
                    // PayPal is not paid until the provider confirms capture.
                    'payment_status' => 'unpaid',
                    'status' => 'new',
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'country' => $validated['country'],
                    'post_code' => $validated['post_code'] ?? null,
                    'address1' => $validated['address1'],
                    'address2' => $validated['address2'] ?? null,
                    'phone' => $validated['phone'],
                    'email' => $validated['email'],
                ]);

                Cart::whereIn('id', $cartItems->pluck('id'))
                    ->where('user_id', auth()->id())
                    ->whereNull('order_id')
                    ->update(['order_id' => $order->id]);

                return $order;
            });

            if (!$order) {
                return back()->with('error', 'Cart is empty.');
            }

            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $details = [
                    'title' => 'New order created',
                    'actionURL' => route('order.show', $order->id),
                    'fas' => 'fa-file-alt',
                ];
                Notification::send($admin, new StatusNotification($details));
            }

            session()->forget(['cart', 'coupon']);

            if ($paymentMethod === 'paypal') {
                return redirect()->route('payment')->with(['id' => $order->id]);
            }

            session()->flash('success', 'Your order has been placed successfully.');
            return redirect()->route('home');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Unable to place the order. Please review the cart and try again.');
        }
    }

        public function show($id)
    {
        $order=Order::find($id);
        // return $order;
        return view('backend.order.show')->with('order',$order);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $order=Order::find($id);
        return view('backend.order.edit')->with('order',$order);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order=Order::find($id);
        $this->validate($request,[
            'status'=>'required|in:new,process,delivered,cancel'
        ]);
        $data=$request->all();
        // return $request->status;
        if($request->status=='delivered'){
            foreach($order->cart as $cart){
                $product=$cart->product;
                // return $product;
                $product->stock -=$cart->quantity;
                $product->save();
            }
        }
        $status=$order->fill($data)->save();
        if($status){
            session()->flash('success','Successfully updated order');
        }
        else{
            session()->flash('error','Error while updating order');
        }
        return redirect()->route('order.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order=Order::find($id);
        if($order){
            $status=$order->delete();
            if($status){
                session()->flash('success','Order Successfully deleted');
            }
            else{
                session()->flash('error','Order can not deleted');
            }
            return redirect()->route('order.index');
        }
        else{
            session()->flash('error','Order can not found');
            return redirect()->back();
        }
    }

    public function orderTrack(){
        return view('frontend.pages.order-track');
    }

    public function productTrackOrder(Request $request){
        // return $request->all();
        $order=Order::where('user_id',auth()->user()->id)->where('order_number',$request->order_number)->first();
        if($order){
            if($order->status=="new"){
            session()->flash('success','Your order has been placed. please wait.');
            return redirect()->route('home');

            }
            elseif($order->status=="process"){
                session()->flash('success','Your order is under processing please wait.');
                return redirect()->route('home');
    
            }
            elseif($order->status=="delivered"){
                session()->flash('success','Your order is successfully delivered.');
                return redirect()->route('home');
    
            }
            else{
                session()->flash('error','Your order canceled. please try again');
                return redirect()->route('home');
    
            }
        }
        else{
            session()->flash('error','Invalid order numer please try again');
            return back();
        }
    }

    // PDF generate
    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $order = Order::getAllOrder($validated['id']);
        if (!$order) {
            abort(404);
        }

        $user = auth()->user();
        if ($user->role !== 'admin' && (int) $order->user_id !== (int) $user->id) {
            // Do not reveal whether another customer's order number exists.
            abort(404);
        }

        $fileName = $order->order_number . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $order->first_name) . '.pdf';
        $pdf = PDF::loadView('backend.order.pdf', compact('order'));

        return $pdf->download($fileName);
    }

        // Income chart
    public function incomeChart(Request $request){
        abort_unless(auth()->check() && auth()->user()->role === 'admin', 403);

        $year=\Carbon\Carbon::now()->year;
        // dd($year);
        $items=Order::with(['cart_info'])->whereYear('created_at',$year)->where('status','delivered')->get()
            ->groupBy(function($d){
                return \Carbon\Carbon::parse($d->created_at)->format('m');
            });
            // dd($items);
        $result=[];
        foreach($items as $month=>$item_collections){
            foreach($item_collections as $item){
                $amount=$item->cart_info->sum('amount');
                // dd($amount);
                $m=intval($month);
                // return $m;
                isset($result[$m]) ? $result[$m] += $amount :$result[$m]=$amount;
            }
        }
        $data=[];
        for($i=1; $i <=12; $i++){
            $monthName=date('F', mktime(0,0,0,$i,1));
            $data[$monthName] = (!empty($result[$i]))? number_format((float)($result[$i]), 2, '.', '') : 0.0;
        }
        return $data;
    }
}
