<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Models\SiteSetting;

class AdminReviewController extends Controller
{
    private function ensureAdmin()
    {
        if ((session('glowskin_user.role') ?? null) !== 'admin') {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login sebagai admin terlebih dahulu.']);
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.reviews.index', [
            'reviews' => ProductReview::with(['product', 'user'])->latest()->get(),
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function toggle(ProductReview $review)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $review->update([
            'is_active' => !$review->is_active,
        ]);

        return back()->with('success', 'Status review berhasil diperbarui.');
    }

    public function destroy(ProductReview $review)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $review->delete();

        return back()->with('success', 'Review berhasil dihapus.');
    }
}
