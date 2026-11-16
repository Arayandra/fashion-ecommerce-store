<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Wishlist;
class WishlistController extends Controller
{
    protected $product=null;
    public function __construct(Product $product){
        $this->product=$product;
    }

    public function wishlist(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return back()->with('error', 'Invalid or unavailable product.');
        }

        $alreadyInWishlist = Wishlist::where('user_id', auth()->id())
            ->whereNull('cart_id')
            ->where('product_id', $product->id)
            ->exists();

        if ($alreadyInWishlist) {
            return back()->with('error', 'You already placed this product in your wishlist.');
        }

        $wishlist = new Wishlist([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'price' => (float) $product->price
                - ((float) $product->price * (float) $product->discount) / 100,
            'quantity' => 1,
        ]);
        $wishlist->amount = $wishlist->price;
        $wishlist->save();

        return back()->with('success', 'Product successfully added to wishlist.');
    }

    public function wishlistDelete(int $id)
    {
        $wishlist = Wishlist::whereKey($id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$wishlist) {
            abort(404);
        }

        $wishlist->delete();

        return back()->with('success', 'Wishlist successfully removed.');
    }
}
