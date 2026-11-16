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

    public function addToCart(Request $request){
        // dd($request->all());
        if (empty($request->slug)) {
            session()->flash('error','Invalid Products');
            return back();
        }        
        $product = Product::where('slug', $request->slug)->first();
        // return $product;
        if (empty($product)) {
            session()->flash('error','Invalid Products');
            return back();
        }

        $already_cart = Cart::where('user_id', auth()->user()->id)->where('order_id',null)->where('product_id', $product->id)->first();
        // return $already_cart;
        if($already_cart) {
            // dd($already_cart);
            $already_cart->quantity = $already_cart->quantity + 1;
            $already_cart->amount = $product->price+ $already_cart->amount;
            // return $already_cart->quantity;
            if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
            $already_cart->save();
            
        }else{
            
            $cart = new Cart;
            $cart->user_id = auth()->user()->id;
            $cart->product_id = $product->id;
            $cart->price = ($product->price-($product->price*$product->discount)/100);
            $cart->quantity = 1;
            $cart->amount=$cart->price*$cart->quantity;
            if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
            $cart->save();
            $wishlist=Wishlist::where('user_id',auth()->user()->id)->where('cart_id',null)->update(['cart_id'=>$cart->id]);
        }
        session()->flash('success','Product successfully added to cart');
        return back();       
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

        public function cartDelete(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $cart = Cart::whereKey($validated['id'])
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
