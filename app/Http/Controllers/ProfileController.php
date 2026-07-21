<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\CheckoutOrder;
use App\Models\CheckoutAddress;
use App\Models\PromoCode;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\DatabaseColumn;

class ProfileController extends Controller
{
    private function authUser()
    {
        return session('glowskin_user');
    }

    private function userRow()
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return null;
        }

        return DB::table('users')->where('id', $authUser['id'])->first();
    }

    public function index()
    {
        $user = $this->userRow();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        return view('profile.index', [
            'user' => $user,
            'settings' => SiteSetting::pluck('value', 'key'),
            'addresses' => CheckoutAddress::where('user_id', $user->id)->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function orders()
    {
        $user = $this->userRow();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $orders = CheckoutOrder::with(['items.product'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('profile.orders', [
            'user' => $user,
            'orders' => $orders,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function vouchers()
    {
        $user = $this->userRow();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $cartSubtotal = (int) CartItem::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->sum(fn ($item) => (int) ($item->product->price ?? 0) * max(1, (int) $item->quantity));

        $allVouchers = PromoCode::query()
            ->orderByRaw("CASE WHEN discount_type = 'percent' THEN 0 WHEN discount_type = 'free_shipping' THEN 1 ELSE 2 END")
            ->latest()
            ->get()
            ->map(function ($voucher) use ($cartSubtotal) {
                $scheduleAvailable = $voucher->is_active
                    && (!$voucher->starts_at || now()->gte($voucher->starts_at))
                    && (!$voucher->expires_at || now()->lte($voucher->expires_at))
                    && (!$voucher->usage_limit || $voucher->used_count < $voucher->usage_limit);

                $voucher->profile_schedule_available = $scheduleAvailable;
                $voucher->profile_is_eligible = $scheduleAvailable && $cartSubtotal >= (int) ($voucher->minimum_purchase ?? 0);
                $voucher->profile_missing_amount = max(0, (int) ($voucher->minimum_purchase ?? 0) - $cartSubtotal);

                return $voucher;
            });

        $definitions = [
            'percent' => [
                'title' => 'Voucher Diskon Persen',
                'subtitle' => 'Potongan berdasarkan persentase total belanja.',
                'icon' => '%',
            ],
            'free_shipping' => [
                'title' => 'Voucher Gratis Ongkir',
                'subtitle' => 'Membantu mengurangi biaya pengiriman pesananmu.',
                'icon' => '✦',
            ],
            'fixed' => [
                'title' => 'Voucher Potongan Harga',
                'subtitle' => 'Potongan nominal langsung dari total belanja.',
                'icon' => 'Rp',
            ],
        ];

        $voucherGroups = collect($definitions)->map(function ($definition, $type) use ($allVouchers) {
            $definition['items'] = $allVouchers
                ->filter(fn ($voucher) => ($voucher->discount_type ?: 'percent') === $type)
                ->values();
            return $definition;
        })->filter(fn ($group) => $group['items']->isNotEmpty());

        return view('profile.vouchers', [
            'user' => $user,
            'settings' => SiteSetting::pluck('value', 'key'),
            'voucherGroups' => $voucherGroups,
            'cartSubtotal' => $cartSubtotal,
            'usableCount' => $allVouchers->where('profile_is_eligible', true)->count(),
            'lockedCount' => $allVouchers->where('profile_schedule_available', true)->where('profile_is_eligible', false)->count(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $this->userRow();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'marketing_consent' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $birthDate = null;
        if (!empty($data['birth_day']) && !empty($data['birth_month']) && !empty($data['birth_year'])) {
            $birthDate = sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']);
        }

        $payload = [
            'name' => $data['name'],
            'updated_at' => now(),
        ];

        if (DatabaseColumn::has('users', 'phone')) {
            $payload['phone'] = $data['phone'] ?? null;
        }

        if (DatabaseColumn::has('users', 'birth_date')) {
            $payload['birth_date'] = $birthDate;
        }

        if (DatabaseColumn::has('users', 'marketing_consent')) {
            $payload['marketing_consent'] = $request->boolean('marketing_consent');
        }

        if ($request->hasFile('profile_image') && DatabaseColumn::has('users', 'profile_image')) {
            $dir = public_path('uploads/profile-images');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $file = $request->file('profile_image');
            $filename = 'profile_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $payload['profile_image'] = 'uploads/profile-images/'.$filename;
        }

        DB::table('users')->where('id', $user->id)->update($payload);

        $freshUser = DB::table('users')->where('id', $user->id)->first();

        session([
            'glowskin_user' => [
                'id' => $freshUser->id,
                'name' => $freshUser->name,
                'email' => $freshUser->email,
                'role' => $freshUser->role ?? ($this->authUser()['role'] ?? 'user'),
                'profile_image' => $freshUser->profile_image ?? null,
            ],
        ]);

        return back()->with('success', 'Profile berhasil diperbarui.');
    }

    public function saveAddress(Request $request)
    {
        $user = $this->userRow();
        if (!$user) return redirect()->route('login');

        $data = $request->validate([
            'address_id' => ['nullable','integer'],
            'label' => ['nullable','string','max:80'],
            'recipient_name' => ['required','string','max:120'],
            'phone' => ['required','string','max:40'],
            'address_line' => ['required','string','max:500'],
            'district' => ['nullable','string','max:120'],
            'city' => ['nullable','string','max:120'],
            'province' => ['nullable','string','max:120'],
            'postal_code' => ['nullable','string','max:20'],
            'country' => ['nullable','string','max:80'],
            'latitude' => ['nullable','numeric'],
            'longitude' => ['nullable','numeric'],
            'map_link' => ['nullable','string','max:500'],
            'courier_note' => ['nullable','string','max:300'],
            'is_default' => ['nullable'],
        ]);
        $address = !empty($data['address_id']) ? CheckoutAddress::where('user_id',$user->id)->find($data['address_id']) : null;
        $makeDefault = $request->boolean('is_default') || !$address && !CheckoutAddress::where('user_id',$user->id)->exists();
        if ($makeDefault) CheckoutAddress::where('user_id',$user->id)->update(['is_default'=>false]);
        $payload = [
            'user_id'=>$user->id,'label'=>$data['label'] ?: 'Home','recipient_name'=>$data['recipient_name'],
            'phone'=>$data['phone'],'address_line'=>$data['address_line'],'district'=>$data['district']??null,
            'city'=>$data['city']??null,'province'=>$data['province']??null,'postal_code'=>$data['postal_code']??null,
            'country'=>$data['country']??'Indonesia','latitude'=>$data['latitude']??null,'longitude'=>$data['longitude']??null,
            'map_link'=>$data['map_link']??null,'courier_note'=>$data['courier_note']??null,'is_default'=>$makeDefault,
        ];
        $address ? $address->update($payload) : CheckoutAddress::create($payload);
        return redirect()->route('profile.index')->with('success','Alamat berhasil disimpan.');
    }

    public function deleteAddress(CheckoutAddress $address)
    {
        $user=$this->userRow();
        abort_unless($user && (int)$address->user_id===(int)$user->id,403);
        $wasDefault=(bool)$address->is_default; $address->delete();
        if($wasDefault){ $next=CheckoutAddress::where('user_id',$user->id)->latest()->first(); if($next)$next->update(['is_default'=>true]); }
        return redirect()->route('profile.index')->with('success','Alamat berhasil dihapus.');
    }

    public function defaultAddress(CheckoutAddress $address)
    {
        $user=$this->userRow();
        abort_unless($user && (int)$address->user_id===(int)$user->id,403);
        CheckoutAddress::where('user_id',$user->id)->update(['is_default'=>false]);
        $address->update(['is_default'=>true]);
        return redirect()->route('profile.index')->with('success','Alamat utama berhasil diubah.');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->userRow();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $data = $request->validate([
            'old_password' => ['required', 'string', 'min:4'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        if (!Hash::check($data['old_password'], $user->password)) {
            return back()->withErrors(['old_password' => 'Password lama tidak sesuai.']);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($data['new_password']),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
