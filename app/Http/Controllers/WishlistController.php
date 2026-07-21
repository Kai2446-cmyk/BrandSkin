<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    private function authUser()
    {
        return session('glowskin_user');
    }

    public function index()
    {
        $user = $this->authUser();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login dulu untuk membuka wishlist.');
        }

        $wishlistProductIds = Wishlist::where('user_id', $user['id'])
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $products = Product::query()
            ->whereIn('id', $wishlistProductIds)
            ->latest()
            ->get();

        return view('wishlist', [
            'settings' => SiteSetting::pluck('value', 'key'),
            'products' => $products,
            'wishlistProductIds' => $wishlistProductIds,
        ]);
    }

    public function ids()
    {
        $user = $this->authUser();

        if (!$user) {
            return response()->json([
                'authenticated' => false,
                'ids' => [],
                'count' => 0,
            ]);
        }

        $ids = Wishlist::where('user_id', $user['id'])
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->values();

        return response()->json([
            'authenticated' => true,
            'ids' => $ids,
            'count' => $ids->count(),
        ]);
    }

    public function toggle(Request $request)
    {
        $user = $this->authUser();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login dulu untuk menyimpan wishlist.',
                'redirect' => route('login'),
            ], 401);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $wishlist = Wishlist::where('user_id', $user['id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $saved = false;
        } else {
            Wishlist::create([
                'user_id' => $user['id'],
                'product_id' => $validated['product_id'],
            ]);
            $saved = true;
        }

        $ids = Wishlist::where('user_id', $user['id'])
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->values();

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'ids' => $ids,
            'count' => $ids->count(),
        ]);
    }
}
