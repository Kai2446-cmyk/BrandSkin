<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class PromoController extends Controller
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

        return view('admin.promos.index', [
            'promos' => PromoCode::latest()->get(),
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function store(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', 'in:percent,fixed,free_shipping'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
            'minimum_purchase' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable'],
            'expires_at' => ['nullable', 'date'],
        ]);

        PromoCode::create([
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? '',
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_type'] === 'free_shipping' ? 0 : (int) ($data['discount_value'] ?? 0),
            'minimum_purchase' => $data['minimum_purchase'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => 0,
            'is_active' => $request->boolean('is_active'),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Voucher berhasil dibuat.');
    }

    public function update(Request $request, PromoCode $promo)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', 'in:percent,fixed,free_shipping'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
            'minimum_purchase' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $promo->update([
            'description' => $data['description'] ?? '',
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_type'] === 'free_shipping' ? 0 : (int) ($data['discount_value'] ?? 0),
            'minimum_purchase' => $data['minimum_purchase'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Voucher berhasil diperbarui.');
    }

    public function destroy(PromoCode $promo)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $promo->delete();

        return back()->with('success', 'Voucher berhasil dihapus.');
    }
}
