<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\Cart;
use Illuminate\Support\Str;
use Helpers;
class CartController extends Controller
{
    protected $product=null;
    public function __construct(Product $product){
        $this->product=$product;
    }

    public function addToCart(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return back()->with('error', 'Invalid or unavailable product.');
        }

        $cart = Cart::where('user_id', auth()->id())
            ->whereNull('order_id')
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = ($cart ? (int) $cart->quantity : 0) + 1;
        if ((int) $product->stock < $newQuantity) {
            return back()->with('error', 'Stock not sufficient.');
        }

        $unitPrice = (float) $product->price
            - ((float) $product->price * (float) $product->discount) / 100;

        if (!$cart) {
            $cart = new Cart([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);
        }

        $cart->price = $unitPrice;
        $cart->quantity = $newQuantity;
        $cart->amount = $unitPrice * $newQuantity;
        $cart->save();

        Wishlist::where('user_id', auth()->id())
            ->whereNull('cart_id')
            ->where('product_id', $product->id)
            ->update(['cart_id' => $cart->id]);

        return back()->with('success', 'Product successfully added to cart.');
    }

    public function singleAddToCart(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
            'quant.1' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::where('slug', $validated['slug'])->firstOrFail();
        $quantity = (int) $validated['quant'][1];

        if ($product->stock < $quantity) {
            return back()->with('error', 'Out of stock. Please choose a smaller quantity.');
        }

        $cart = Cart::where('user_id', auth()->id())
            ->whereNull('order_id')
            ->where('product_id', $product->id)
            ->first();

        $unitPrice = $product->price - ($product->price * $product->discount) / 100;
        $newQuantity = ($cart ? (int) $cart->quantity : 0) + $quantity;

        if ($product->stock < $newQuantity) {
            return back()->with('error', 'Stock not sufficient.');
        }

        if (!$cart) {
            $cart = new Cart([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);
        }

        $cart->price = $unitPrice;
        $cart->quantity = $newQuantity;
        $cart->amount = $unitPrice * $newQuantity;
        $cart->save();

        session()->flash('success', 'Product successfully added to cart.');
        return back();
    }

    public function cartDelete(int $id)
    {
        $cart = Cart::whereKey($id)
            ->where('user_id', auth()->id())
            ->whereNull('order_id')
            ->first();

        if (!$cart) {
            abort(404);
        }

        $cart->delete();

        return back()->with('success', 'Cart successfully removed.');
    }

        public function cartUpdate(Request $request)
    {
        $validated = $request->validate([
            'quant' => ['required', 'array', 'min:1'],
            'quant.*' => ['required', 'integer', 'min:1', 'max:999'],
            'qty_id' => ['required', 'array'],
            'qty_id.*' => ['required', 'integer'],
        ]);

        foreach ($validated['quant'] as $key => $quantity) {
            $cartId = $validated['qty_id'][$key] ?? null;
            if (!$cartId) {
                return back()->with('error', 'Invalid cart item.');
            }

            $cart = Cart::whereKey($cartId)
                ->where('user_id', auth()->id())
                ->whereNull('order_id')
                ->with('product')
                ->first();

            if (!$cart || !$cart->product) {
                abort(404);
            }

            if ((int) $cart->product->stock < (int) $quantity) {
                return back()->with('error', 'Stock not sufficient for ' . $cart->product->title . '.');
            }

            $unitPrice = $cart->product->price - (
                $cart->product->price * $cart->product->discount
            ) / 100;

            $cart->price = $unitPrice;
            $cart->quantity = (int) $quantity;
            $cart->amount = $unitPrice * (int) $quantity;
            $cart->save();
        }

        return back()->with('success', 'Cart successfully updated.');
    }

    // public function addToCart(Request $request){
    //     // return $request->all();
    //     if(Auth::check()){
    //         $qty=$request->quantity;
    //         $this->product=$this->product->find($request->pro_id);
    //         if($this->product->stock < $qty){
    //             return response(['status'=>false,'msg'=>'Out of stock','data'=>null]);
    //         }
    //         if(!$this->product){
    //             return response(['status'=>false,'msg'=>'Product not found','data'=>null]);
    //         }
    //         // $session_id=session('cart')['session_id'];
    //         // if(empty($session_id)){
    //         //     $session_id=Str::random(30);
    //         //     // dd($session_id);
    //         //     session()->put('session_id',$session_id);
    //         // }
    //         $current_item=array(
    //             'user_id'=>auth()->user()->id,
    //             'id'=>$this->product->id,
    //             // 'session_id'=>$session_id,
    //             'title'=>$this->product->title,
    //             'summary'=>$this->product->summary,
    //             'link'=>route('product-detail',$this->product->slug),
    //             'price'=>$this->product->price,
    //             'photo'=>$this->product->photo,
    //         );
            
    //         $price=$this->product->price;
    //         if($this->product->discount){
    //             $price=($price-($price*$this->product->discount)/100);
    //         }
    //         $current_item['price']=$price;

    //         $cart=session('cart') ? session('cart') : null;

    //         if($cart){
    //             // if anyone alreay order products
    //             $index=null;
    //             foreach($cart as $key=>$value){
    //                 if($value['id']==$this->product->id){
    //                     $index=$key;
    //                 break;
    //                 }
    //             }
    //             if($index!==null){
    //                 $cart[$index]['quantity']=$qty;
    //                 $cart[$index]['amount']=ceil($qty*$price);
    //                 if($cart[$index]['quantity']<=0){
    //                     unset($cart[$index]);
    //                 }
    //             }
    //             else{
    //                 $current_item['quantity']=$qty;
    //                 $current_item['amount']=ceil($qty*$price);
    //                 $cart[]=$current_item;
    //             }
    //         }
    //         else{
    //             $current_item['quantity']=$qty;
    //             $current_item['amount']=ceil($qty*$price);
    //             $cart[]=$current_item;
    //         }

    //         session()->put('cart',$cart);
    //         return response(['status'=>true,'msg'=>'Cart successfully updated','data'=>$cart]);
    //     }
    //     else{
    //         return response(['status'=>false,'msg'=>'You need to login first','data'=>null]);
    //     }
    // }

    // public function removeCart(Request $request){
    //     $index=$request->index;
    //     // return $index;
    //     $cart=session('cart');
    //     unset($cart[$index]);
    //     session()->put('cart',$cart);
    //     return redirect()->back()->with('success','Successfully remove item');
    // }

    public function checkout(Request $request){
        // $cart=session('cart');
        // $cart_index=\Str::random(10);
        // $sub_total=0;
        // foreach($cart as $cart_item){
        //     $sub_total+=$cart_item['amount'];
        //     $data=array(
        //         'cart_id'=>$cart_index,
        //         'user_id'=>$request->user()->id,
        //         'product_id'=>$cart_item['id'],
        //         'quantity'=>$cart_item['quantity'],
        //         'amount'=>$cart_item['amount'],
        //         'status'=>'new',
        //         'price'=>$cart_item['price'],
        //     );

        //     $cart=new Cart();
        //     $cart->fill($data);
        //     $cart->save();
        // }
        return view('frontend.pages.checkout');
    }
}
