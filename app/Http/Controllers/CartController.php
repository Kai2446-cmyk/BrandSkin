<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\CheckoutAddress;
use App\Models\CheckoutOrder;
use App\Models\CheckoutOrderItem;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\PromoCode;
use App\Models\SiteSetting;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function authUser()
    {
        return session('glowskin_user');
    }

    private function loginResponse(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => 'Silakan login terlebih dahulu untuk memasukkan produk ke shopping bag.',
                'login_url' => route('login'),
            ], 401);
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Silakan login terlebih dahulu untuk memasukkan produk ke shopping bag atau membeli produk.']);
    }


    private function rajaOngkirRequest()
    {
        $verify = filter_var(env('RAJAONGKIR_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN);

        $apiKey = trim((string) env('RAJAONGKIR_KOMERCE_API_KEY'));

        $client = Http::timeout(12)
            ->connectTimeout(6)
            ->withHeaders([
                // RajaOngkir/Komerce Shipping Cost memakai header `key`.
                // Jangan kirim header Authorization/Key ganda agar request stabil
                // dan tidak dianggap request berbeda oleh gateway API.
                'key' => $apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'GlowSkin-Laravel-Checkout/1.0',
            ]);

        /*
         * Di localhost Windows/XAMPP/Laragon sering muncul cURL error 60
         * karena certificate bundle belum terbaca. Untuk local development,
         * SSL verification dimatikan otomatis supaya ongkir tetap bisa jalan.
         * Di production tetap mengikuti RAJAONGKIR_SSL_VERIFY=true.
         */
        if (!$verify || app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function rajaOngkirErrorResponse(array $extra = [], int $status = 422)
    {
        return response()->json(array_merge([
            'ok' => false,
            'message' => 'Ongkir belum bisa dimuat. Silakan coba beberapa saat lagi atau lengkapi alamat pengiriman.',
        ], $extra), $status);
    }

    private function cartSummary(int $userId): array
    {
        $items = CartItem::with('product')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $subtotal = $items->sum(function ($item) {
            return ($item->product->price ?? 0) * $item->quantity;
        });

        $promo = session('glowskin_promo');
        $discount = 0;

        if ($promo) {
            $promoType = $promo['type'] ?? 'percent';

            if ($promoType === 'free_shipping') {
                $discount = 0;
            } else {
                $discount = (int) ($promo['discount'] ?? 0);
                if ($promoType === 'percent') {
                    $discount = (int) round($subtotal * ($discount / 100));
                }
                $discount = min($discount, $subtotal);
            }
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'promo' => $promo,
        ];
    }


    private function checkoutVoucherGroups(int $subtotal): array
    {
        $empty = collect();
        $groups = [
            'free_shipping' => [
                'title' => 'Gratis Ongkir',
                'description' => 'Voucher khusus potongan biaya pengiriman.',
                'items' => $empty,
            ],
            'percent' => [
                'title' => 'Diskon Persen',
                'description' => 'Potongan berdasarkan persentase belanja.',
                'items' => $empty,
            ],
            'fixed' => [
                'title' => 'Potongan Rupiah',
                'description' => 'Potongan nominal langsung.',
                'items' => $empty,
            ],
        ];

        $vouchers = PromoCode::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN discount_type = 'free_shipping' THEN 0 WHEN discount_type = 'percent' THEN 1 ELSE 2 END")
            ->latest()
            ->get()
            ->map(function ($promo) use ($subtotal) {
                $promo->checkout_is_eligible = $promo->isValidFor($subtotal);
                $promo->checkout_missing_amount = max(0, (int) ($promo->minimum_purchase ?? 0) - $subtotal);
                return $promo;
            });

        foreach ($groups as $type => $group) {
            $groups[$type]['items'] = $vouchers
                ->filter(fn ($promo) => ($promo->discount_type ?: 'percent') === $type)
                ->values();
        }

        return collect($groups)
            ->filter(fn ($group) => collect($group['items'] ?? [])->isNotEmpty())
            ->all();
    }

    public function checkout(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu untuk melanjutkan checkout.']);
        }

        $summary = $this->cartSummary((int) $authUser['id']);

        if ($summary['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Shopping bag masih kosong.']);
        }

        $addresses = CheckoutAddress::where('user_id', $authUser['id'])
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?: $addresses->first();

        return view('checkout.index', array_merge($summary, [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'authUser' => $authUser,
            'settings' => SiteSetting::pluck('value', 'key'),
            'voucherGroups' => $this->checkoutVoucherGroups((int) $summary['subtotal']),
            'shippingWeight' => max(1000, (int) $summary['items']->sum(fn ($item) => max(1, (int) $item->quantity) * (int) env('RAJAONGKIR_DEFAULT_ITEM_WEIGHT', 1000))),
        ]));
    }

    public function confirmCheckout(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        $summary = $this->cartSummary((int) $authUser['id']);

        if ($summary['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Shopping bag masih kosong.']);
        }

        $defaultAddress = CheckoutAddress::where('user_id', $authUser['id'])
            ->where('is_default', true)
            ->first() ?: CheckoutAddress::where('user_id', $authUser['id'])->latest()->first();

        if (!$defaultAddress) {
            return redirect()->route('checkout.index')->withErrors(['address' => 'Tambahkan alamat pengiriman terlebih dahulu.']);
        }

        $data = $request->validate([
            'shipping_cost' => ['nullable', 'integer', 'min:0'],
            'shipping_courier' => ['nullable', 'string', 'max:120'],
            'shipping_service' => ['nullable', 'string', 'max:120'],
            'shipping_etd' => ['nullable', 'string', 'max:80'],
            'shipping_description' => ['nullable', 'string', 'max:200'],
        ]);

        $shippingCost = max(0, (int) ($data['shipping_cost'] ?? 0));
        if (($summary['promo']['type'] ?? null) === 'free_shipping') {
            $shippingCost = 0;
        }
        $shippingCourier = trim((string) ($data['shipping_courier'] ?? ''));
        $shippingService = trim((string) ($data['shipping_service'] ?? ''));

        session([
            'glowskin_checkout_review' => [
                'address_id' => $defaultAddress->id,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $shippingCourier ?: 'Belum dipilih',
                'shipping_service' => $shippingService ?: '-',
                'shipping_etd' => trim((string) ($data['shipping_etd'] ?? '-')) ?: '-',
                'shipping_description' => trim((string) ($data['shipping_description'] ?? '')),
                'subtotal' => (int) $summary['subtotal'],
                'discount' => (int) $summary['discount'],
                'total' => max(0, (int) $summary['total'] + $shippingCost),
                'created_at' => now()->toDateTimeString(),
            ],
        ]);

        return redirect()->route('checkout.review');
    }

    public function reviewCheckout(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu untuk melanjutkan payment.']);
        }

        $summary = $this->cartSummary((int) $authUser['id']);

        if ($summary['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Shopping bag masih kosong.']);
        }

        $review = session('glowskin_checkout_review');

        if (!$review) {
            return redirect()->route('checkout.index')->withErrors(['checkout' => 'Pilih alamat dan pengiriman terlebih dahulu.']);
        }

        $address = CheckoutAddress::where('user_id', $authUser['id'])
            ->find($review['address_id'] ?? null);

        if (!$address) {
            session()->forget('glowskin_checkout_review');
            return redirect()->route('checkout.index')->withErrors(['address' => 'Alamat pengiriman belum ditemukan. Pilih ulang alamat checkout.']);
        }

        return view('checkout.review', [
            'authUser' => $authUser,
            'items' => $summary['items'],
            'subtotal' => (int) $summary['subtotal'],
            'discount' => (int) $summary['discount'],
            'shippingCost' => (int) ($review['shipping_cost'] ?? 0),
            'grandTotal' => max(0, (int) $summary['total'] + (int) ($review['shipping_cost'] ?? 0)),
            'promo' => $summary['promo'],
            'address' => $address,
            'shipping' => $review,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    private function midtransPaymentError(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()
            ->route('checkout.review')
            ->withErrors(['payment' => $message]);
    }


    private function createCheckoutOrderForPayment($authUser, $address, array $summary, array $review, string $midtransOrderId): CheckoutOrder
    {
        return DB::transaction(function () use ($authUser, $address, $summary, $review, $midtransOrderId) {
            $shippingCost = (int) ($review['shipping_cost'] ?? 0);
            $grandTotal = max(0, (int) $summary['total'] + $shippingCost);

            $order = CheckoutOrder::create([
                'user_id' => (int) $authUser['id'],
                'order_code' => 'GS-' . now()->format('YmdHis') . '-' . (int) $authUser['id'],
                'midtrans_order_id' => $midtransOrderId,
                'payment_status' => 'pending',
                'subtotal' => (int) $summary['subtotal'],
                'discount' => (int) $summary['discount'],
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'shipping_courier' => (string) ($review['shipping_courier'] ?? ''),
                'shipping_service' => (string) ($review['shipping_service'] ?? ''),
                'shipping_etd' => (string) ($review['shipping_etd'] ?? ''),
                'shipping_description' => (string) ($review['shipping_description'] ?? ''),
                'recipient_name' => (string) ($address->recipient_name ?? ''),
                'phone' => (string) ($address->phone ?? ''),
                'address_line' => (string) ($address->address_line ?? ''),
                'district' => (string) ($address->district ?? ''),
                'city' => (string) ($address->city ?? ''),
                'province' => (string) ($address->province ?? ''),
                'postal_code' => (string) ($address->postal_code ?? ''),
                'map_link' => (string) ($address->map_link ?? ''),
                'courier_note' => (string) ($address->courier_note ?? ''),
            ]);

            foreach ($summary['items'] as $item) {
                $product = $item->product;
                if (!$product) continue;

                CheckoutOrderItem::create([
                    'checkout_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => (string) $product->name,
                    'product_image' => (string) ($product->image ?? ''),
                    'price' => (int) ($product->price ?? 0),
                    'quantity' => (int) $item->quantity,
                ]);
            }

            return $order;
        });
    }

    private function finalizeCheckoutOrder(string $midtransOrderId, string $status, array $payload = []): ?CheckoutOrder
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return null;
        }

        $order = CheckoutOrder::with('items.product')
            ->where('user_id', (int) $authUser['id'])
            ->where('midtrans_order_id', $midtransOrderId)
            ->first();

        if (!$order) {
            return null;
        }

        $targetStatus = in_array($status, ['paid', 'success', 'settlement', 'capture'], true) ? 'paid' : (in_array($status, ['failed', 'failure', 'deny', 'denied', 'cancel', 'cancelled', 'expire', 'expired'], true) ? 'failed' : 'pending');
        $wasPaid = $order->payment_status === 'paid';

        DB::transaction(function () use ($order, $targetStatus, $payload, $wasPaid) {
            $order->update([
                'payment_status' => $targetStatus,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $order->midtrans_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $order->payment_type,
                'paid_at' => $targetStatus === 'paid' ? ($order->paid_at ?: now()) : $order->paid_at,
            ]);

            if ($targetStatus === 'paid' && !$wasPaid) {
                foreach ($order->items as $item) {
                    $purchase = ProductPurchase::firstOrNew([
                        'user_id' => $order->user_id,
                        'product_id' => $item->product_id,
                    ]);

                    $purchase->quantity = max((int) ($purchase->quantity ?? 0), (int) $item->quantity);
                    $purchase->status = 'paid';
                    $purchase->purchased_at = now();
                    $purchase->save();

                    if ($item->product && \App\Support\DatabaseColumn::has('products', 'sold_count')) {
                        $item->product->increment('sold_count', (int) $item->quantity);
                    }
                }
            }

            CartItem::where('user_id', $order->user_id)->delete();
        });

        session()->forget(['glowskin_checkout_review', 'glowskin_promo']);

        return $order->fresh('items.product');
    }

    public function startMidtransPayment(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        $summary = $this->cartSummary((int) $authUser['id']);
        $review = session('glowskin_checkout_review');

        if ($summary['items']->isEmpty() || !$review) {
            return $this->midtransPaymentError($request, 'Data checkout belum lengkap. Silakan kembali ke checkout.', 422);
        }

        $address = CheckoutAddress::where('user_id', $authUser['id'])
            ->find($review['address_id'] ?? null);

        if (!$address) {
            return $this->midtransPaymentError($request, 'Alamat pengiriman belum ditemukan. Silakan pilih alamat dulu.', 422);
        }

        $serverKey = trim((string) env('MIDTRANS_SERVER_KEY'));

        if ($serverKey === '') {
            return $this->midtransPaymentError($request, 'Pembayaran belum bisa dibuka. Server Key Midtrans belum diisi.', 422);
        }

        /*
         * ENV menjadi sumber utama mode Midtrans.
         * Sandbox dashboard  => MIDTRANS_IS_PRODUCTION=false
         * Production dashboard => MIDTRANS_IS_PRODUCTION=true
         */
        $isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);

        $snapEndpoint = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $shippingCost = (int) ($review['shipping_cost'] ?? 0);
        $grossAmount = max(0, (int) $summary['total'] + $shippingCost);
        $orderId = 'GLOWSKIN-' . now()->format('YmdHis') . '-' . $authUser['id'] . '-' . Str::upper(Str::random(5));
        $checkoutOrder = $this->createCheckoutOrderForPayment($authUser, $address, $summary, $review, $orderId);

        $itemDetails = [];
        foreach ($summary['items'] as $item) {
            $product = $item->product;
            if (!$product) continue;
            $itemDetails[] = [
                'id' => 'PRODUCT-' . $product->id,
                'price' => (int) $product->price,
                'quantity' => (int) $item->quantity,
                'name' => Str::limit((string) $product->name, 45, ''),
            ];
        }

        if ($summary['discount'] > 0) {
            $itemDetails[] = [
                'id' => 'DISCOUNT',
                'price' => -1 * (int) $summary['discount'],
                'quantity' => 1,
                'name' => 'Promo Discount',
            ];
        }

        if ($shippingCost > 0) {
            $itemDetails[] = [
                'id' => 'SHIPPING',
                'price' => $shippingCost,
                'quantity' => 1,
                'name' => Str::limit(($review['shipping_courier'] ?? 'Shipping') . ' ' . ($review['shipping_service'] ?? ''), 45, ''),
            ];
        }

        try {
            $midtransHttp = Http::timeout(25)
                ->connectTimeout(10)
                ->withBasicAuth($serverKey, '')
                ->acceptJson();

            if (!filter_var(env('MIDTRANS_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) || app()->environment('local')) {
                $midtransHttp = $midtransHttp->withoutVerifying();
            }

            $response = $midtransHttp->post($snapEndpoint, [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $address->recipient_name ?: ($authUser['name'] ?? 'GlowSkin Customer'),
                    'email' => $authUser['email'] ?? null,
                    'phone' => $address->phone,
                    'shipping_address' => [
                        'first_name' => $address->recipient_name,
                        'phone' => $address->phone,
                        'address' => $address->address_line,
                        'city' => $address->city,
                        'postal_code' => $address->postal_code,
                        'country_code' => 'IDN',
                    ],
                ],
                'item_details' => $itemDetails,
                'callbacks' => [
                    'finish' => route('checkout.success', ['order' => $checkoutOrder->id]),
                ],
            ]);

            if (!$response->successful()) {
                $checkoutOrder->delete();
                report(new \RuntimeException('Midtrans Snap error [' . $response->status() . ']: ' . $response->body()));

                return $this->midtransPaymentError(
                    $request,
                    'Pembayaran Midtrans belum bisa dibuka. Cek kembali Server Key Midtrans dan koneksi internet server.',
                    422
                );
            }

            $snapToken = $response->json('token');
            $redirectUrl = $response->json('redirect_url');

            if (!$snapToken && !$redirectUrl) {
                $checkoutOrder->delete();
                report(new \RuntimeException('Midtrans Snap response tidak punya redirect_url/token: ' . $response->body()));
                return $this->midtransPaymentError($request, 'Payment Midtrans belum bisa dibuka. Coba klik Bayar Sekarang lagi.', 422);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'snap_token' => $snapToken,
                    'redirect_url' => $redirectUrl,
                    'order_id' => $orderId,
                ]);
            }

            // Fallback kalau JavaScript mati: tetap arahkan ke halaman resmi Midtrans.
            if (!$redirectUrl && $snapToken) {
                $redirectUrl = ($isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com') . '/snap/v2/vtweb/' . $snapToken;
            }

            return redirect()->away($redirectUrl);
        } catch (\Throwable $error) {
            report($error);
            return $this->midtransPaymentError($request, 'Payment Midtrans belum bisa dibuka. Coba beberapa saat lagi.', 500);
        }
    }


    public function completeMidtransPayment(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:120'],
            'transaction_status' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
            'payment_type' => ['nullable', 'string', 'max:80'],
        ]);

        $rawStatus = $data['transaction_status'] ?? $data['status'] ?? 'pending';
        $paidStatuses = ['capture', 'settlement', 'success', 'paid'];
        $failedStatuses = ['deny', 'denied', 'cancel', 'cancelled', 'expire', 'expired', 'failure', 'failed'];
        $status = in_array($rawStatus, $paidStatuses, true) ? 'paid' : (in_array($rawStatus, $failedStatuses, true) ? 'failed' : 'pending');

        $order = $this->finalizeCheckoutOrder($data['order_id'], $status, $data);

        if (!$order) {
            return response()->json([
                'ok' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'status' => $order->payment_status,
            'redirect_url' => route('checkout.success', $order),
            'cart_count' => 0,
        ]);
    }

    public function paymentSuccess(CheckoutOrder $order)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        abort_unless((int) $order->user_id === (int) $authUser['id'], 403);

        $order->load('items.product');

        return view('checkout.success', [
            'order' => $order,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function saveCheckoutAddress(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'address_line' => ['required', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'map_link' => ['nullable', 'string', 'max:500'],
            'courier_note' => ['nullable', 'string', 'max:300'],
        ]);

        CheckoutAddress::where('user_id', $authUser['id'])->update(['is_default' => false]);

        $address = null;
        if (!empty($data['address_id'])) {
            $address = CheckoutAddress::where('user_id', $authUser['id'])->find($data['address_id']);
        }

        $payload = [
            'user_id' => $authUser['id'],
            'label' => $data['label'] ?: 'Home',
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address_line' => $data['address_line'],
            'district' => $data['district'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? 'Indonesia',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'map_link' => $data['map_link'] ?? null,
            'courier_note' => $data['courier_note'] ?? null,
            'is_default' => true,
        ];

        if ($address) {
            $address->update($payload);
        } else {
            CheckoutAddress::create($payload);
        }

        return redirect()->route('checkout.index')->with('success', 'Delivery address berhasil disimpan.');
    }

    public function deleteCheckoutAddress(Request $request, CheckoutAddress $address)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        abort_unless((int) $address->user_id === (int) $authUser['id'], 403);

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = CheckoutAddress::where('user_id', $authUser['id'])->latest()->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Alamat berhasil dihapus.',
        ]);
    }

    public function index(Request $request)
    {
        $authUser = $this->authUser();

        /*
         * FIX:
         * User yang belum login tidak boleh melihat/memakai cart.
         * Langsung diarahkan ke halaman login.
         */
        if (!$authUser || empty($authUser['id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu untuk melihat shopping bag dan membeli produk.']);
        }

        $items = CartItem::with('product')
            ->where('user_id', $authUser['id'])
            ->latest()
            ->get();

        $wishlistProducts = Wishlist::with('product')
            ->where('user_id', $authUser['id'])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter()
            ->values();

        $subtotal = $items->sum(function ($item) {
            return ($item->product->price ?? 0) * $item->quantity;
        });

        $promo = session('glowskin_promo');
        $discount = 0;

        if ($promo) {
            $discount = (int) ($promo['discount'] ?? 0);
            if (($promo['type'] ?? 'percent') === 'percent') {
                $discount = (int) round($subtotal * ($discount / 100));
            }
            $discount = min($discount, $subtotal);
        }

        return view('cart.index', [
            'items' => $items,
            'wishlistProducts' => $wishlistProducts,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'promo' => $promo,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function count(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return response()->json(['count' => 0]);
        }

        return response()->json([
            'count' => (int) CartItem::where('user_id', $authUser['id'])->sum('quantity'),
        ]);
    }

    public function add(Request $request)
    {
        $authUser = $this->authUser();

        /*
         * FIX:
         * Add to bag harus login dulu.
         * Kalau belum login, AJAX menerima 401 + login_url, lalu JS redirect ke /login.
         */
        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        $item = CartItem::firstOrNew([
            'user_id' => $authUser['id'],
            'product_id' => $product->id,
        ]);

        $item->quantity = (int) ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();

        return response()->json([
            'ok' => true,
            'message' => 'Produk berhasil ditambahkan ke shopping bag.',
            'count' => (int) CartItem::where('user_id', $authUser['id'])->sum('quantity'),
            'item' => [
                'id' => $item->id,
                'quantity' => $item->quantity,
            ],
        ]);
    }

    public function update(Request $request, CartItem $item)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        abort_unless((int) $item->user_id === (int) $authUser['id'], 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item->update([
            'quantity' => (int) $data['quantity'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Quantity shopping bag berhasil diperbarui.',
            'count' => (int) CartItem::where('user_id', $authUser['id'])->sum('quantity'),
        ]);
    }

    public function remove(Request $request, CartItem $item)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        abort_unless((int) $item->user_id === (int) $authUser['id'], 403);

        $item->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Produk berhasil dihapus dari shopping bag.',
            'count' => (int) CartItem::where('user_id', $authUser['id'])->sum('quantity'),
        ]);
    }


    public function searchShippingDestination(Request $request)
    {
        $query = trim((string) $request->query('search', ''));
        $apiKey = env('RAJAONGKIR_KOMERCE_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'ok' => false,
                'message' => 'Layanan pengiriman belum siap digunakan.',
                'data' => [],
            ], 422);
        }

        if (mb_strlen($query) < 3) {
            return response()->json([
                'ok' => false,
                'message' => 'Ketik minimal 3 huruf tujuan pengiriman.',
                'data' => [],
            ], 422);
        }

        try {
            $response = $this->rajaOngkirRequest()
                ->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                    'search' => $query,
                    'limit' => 8,
                    'offset' => 0,
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal mengambil tujuan dari RajaOngkir Komerce.',
                    'data' => [],
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'data' => $response->json('data') ?? [],
            ]);
        } catch (\Throwable $error) {
            report($error);

            return $this->rajaOngkirErrorResponse(['data' => []]);
        }
    }


    private function cleanRajaOngkirLocationText(?string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim((string) $value));
        $value = preg_replace('/\b(kab\.|kabupaten|kota|provinsi)\b/i', '', $value);
        $value = preg_replace('/\b(indonesia)\b/i', '', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));
        return $value;
    }

    private function buildDestinationCandidates(array $data): array
    {
        $district = trim((string) ($data['district'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $province = trim((string) ($data['province'] ?? ''));
        $postalCode = trim((string) ($data['postal_code'] ?? ''));
        $query = trim((string) ($data['destination_query'] ?? ''));

        /*
         * Bikin hemat hit API: prioritaskan query yang paling mungkin cocok.
         * Jangan lagi memecah alamat panjang menjadi belasan request karena itu
         * membuat kuota RajaOngkir cepat habis dan halaman terlihat loading terus.
         */
        $raw = [
            trim($district . ' ' . $city . ' ' . $province . ' ' . $postalCode),
            trim($district . ' ' . $city . ' ' . $province),
            trim($postalCode . ' ' . $city),
            trim($district . ' ' . $city),
            trim($city . ' ' . $province),
            $query,
        ];

        return collect($raw)
            ->map(fn ($item) => preg_replace('/\s+/', ' ', trim((string) $item)))
            ->filter(fn ($item) => mb_strlen($item) >= 3)
            ->unique()
            ->take(4)
            ->values()
            ->all();
}

    private function scoreRajaOngkirDestination(array $item, array $context = []): int
    {
        $text = mb_strtolower(collect([
            $item['label'] ?? null,
            $item['subdistrict_name'] ?? null,
            $item['district_name'] ?? null,
            $item['city_name'] ?? null,
            $item['province_name'] ?? null,
            $item['zip_code'] ?? null,
        ])->filter()->implode(' '));

        $score = 0;

        foreach ([
            'postal_code' => 80,
            'district' => 45,
            'city' => 35,
            'province' => 25,
        ] as $field => $point) {
            $needle = mb_strtolower($this->cleanRajaOngkirLocationText($context[$field] ?? ''));
            if ($needle !== '' && str_contains($text, $needle)) {
                $score += $point;
            }
        }

        return $score;
    }

    private function findRajaOngkirDestinationMatches(array $candidates, array $context = [], int $limit = 3): array
    {
        $matches = collect();

        foreach (array_slice($candidates, 0, 4) as $candidate) {
            $cacheKey = 'rajaongkir_destination_' . md5(mb_strtolower($candidate));

            try {
                $data = Cache::remember($cacheKey, now()->addDays(7), function () use ($candidate) {
                    $response = $this->rajaOngkirRequest()
                        ->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                            'search' => $candidate,
                            'limit' => 5,
                            'offset' => 0,
                        ]);

                    if ($response->status() === 429) {
                        Log::warning('RajaOngkir checkout quota limit', [
                            'step' => 'destination-search',
                            'candidate' => $candidate,
                            'status' => 429,
                            'body' => $response->body(),
                        ]);
                        return ['__quota_limit' => true];
                    }

                    if (!$response->successful()) {
                        Log::warning('RajaOngkir checkout problem', [
                            'step' => 'destination-search',
                            'candidate' => $candidate,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                        return [];
                    }

                    return $response->json('data') ?? [];
                });

                if (isset($data['__quota_limit'])) {
                    break;
                }

                foreach (collect($data)->filter() as $item) {
                    $item = (array) $item;
                    $id = (int) ($item['id'] ?? 0);

                    if ($id <= 0) {
                        continue;
                    }

                    $score = $this->scoreRajaOngkirDestination($item, $context);
                    $candidateNeedle = mb_strtolower($this->cleanRajaOngkirLocationText($candidate));
                    $itemText = mb_strtolower(collect([
                        $item['label'] ?? null,
                        $item['subdistrict_name'] ?? null,
                        $item['district_name'] ?? null,
                        $item['city_name'] ?? null,
                        $item['province_name'] ?? null,
                        $item['zip_code'] ?? null,
                    ])->filter()->implode(' '));

                    if ($candidateNeedle !== '' && str_contains($itemText, $candidateNeedle)) {
                        $score += 15;
                    }

                    $matches->push([
                        'id' => $id,
                        'score' => $score,
                        'label' => $item['label'] ?? $itemText,
                    ]);
                }
            } catch (\Throwable $error) {
                report($error);
                continue;
            }

            if ($matches->isNotEmpty()) {
                break;
            }
        }

        return $matches
            ->sortByDesc('score')
            ->unique('id')
            ->take($limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function findRajaOngkirDestinationId(array $candidates, array $context = []): ?int
    {
        return $this->findRajaOngkirDestinationMatches($candidates, $context, 1)[0] ?? null;
    }


    private function extractRajaOngkirRates($payload, string $fallbackCourier = ''): array
    {
        $rows = [];
        $data = data_get($payload, 'data', $payload);

        $walk = function ($node) use (&$walk, &$rows, $fallbackCourier) {
            if (!is_array($node)) {
                return;
            }

            $hasCost = array_key_exists('cost', $node) || array_key_exists('value', $node) || array_key_exists('price', $node);
            $hasService = array_key_exists('service', $node) || array_key_exists('service_name', $node);

            if ($hasCost && $hasService) {
                $cost = (int) ($node['cost'] ?? $node['value'] ?? $node['price'] ?? 0);
                if ($cost > 0) {
                    $rows[] = [
                        'courier_name' => $node['name'] ?? $node['courier_name'] ?? strtoupper($fallbackCourier),
                        'courier_code' => $node['code'] ?? $node['courier_code'] ?? $fallbackCourier,
                        'service' => $node['service'] ?? $node['service_name'] ?? '-',
                        'description' => $node['description'] ?? $node['desc'] ?? $node['service_description'] ?? null,
                        'cost' => $cost,
                        'etd' => $node['etd'] ?? $node['estimated_delivery_time'] ?? $node['duration'] ?? '-',
                    ];
                }

                return;
            }

            // Beberapa wrapper lama punya format costs[0].cost[0].value
            if (isset($node['costs']) && is_array($node['costs'])) {
                foreach ($node['costs'] as $service) {
                    $service = (array) $service;
                    foreach ((array) ($service['cost'] ?? []) as $costRow) {
                        $costRow = (array) $costRow;
                        $cost = (int) ($costRow['value'] ?? 0);
                        if ($cost > 0) {
                            $rows[] = [
                                'courier_name' => $node['name'] ?? $node['courier_name'] ?? strtoupper($fallbackCourier),
                                'courier_code' => $node['code'] ?? $node['courier_code'] ?? $fallbackCourier,
                                'service' => $service['service'] ?? '-',
                                'description' => $service['description'] ?? null,
                                'cost' => $cost,
                                'etd' => $costRow['etd'] ?? '-',
                            ];
                        }
                    }
                }
                return;
            }

            foreach ($node as $child) {
                $walk($child);
            }
        };

        $walk($data);

        return $rows;
    }

    private function rajaOngkirCostPayload(int $originId, int $destinationId, int $weight, string $courier): array
    {
        return [
            'origin' => $originId,
            'destination' => $destinationId,
            'weight' => $weight,
            'courier' => $courier,
            'price' => 'lowest',
        ];
    }

    public function shippingRates(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        $apiKey = trim((string) env('RAJAONGKIR_KOMERCE_API_KEY'));
        $originId = (int) env('RAJAONGKIR_ORIGIN_ID');

        if (!$apiKey || !$originId) {
            return response()->json([
                'ok' => false,
                'message' => 'Layanan pengiriman belum siap digunakan.',
                'options' => [],
            ], 422);
        }

        $data = $request->validate([
            'destination_id' => ['nullable', 'integer'],
            'destination_query' => ['nullable', 'string', 'max:220'],
            'destination_candidates' => ['nullable', 'array'],
            'destination_candidates.*' => ['nullable', 'string', 'max:220'],
            'district' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
            'province' => ['nullable', 'string', 'max:140'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'weight' => ['nullable', 'integer', 'min:1'],
        ]);

        $weight = max(1000, (int) ($data['weight'] ?? env('RAJAONGKIR_DEFAULT_ITEM_WEIGHT', 1000)));
        $candidateQueries = $this->buildDestinationCandidates($data);

        $destinationIds = collect();
        if (!empty($data['destination_id'])) {
            $destinationIds->push((int) $data['destination_id']);
        }

        $destinationIds = $destinationIds
            ->merge($this->findRajaOngkirDestinationMatches($candidateQueries, $data, 2))
            ->filter()
            ->unique()
            ->values();

        if ($destinationIds->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Ongkir belum bisa dimuat. Pilih ulang titik maps atau lengkapi kecamatan/kota/kode pos.',
                'options' => [],
            ], 422);
        }

        $couriers = collect(explode(',', (string) env('RAJAONGKIR_COURIERS', 'jne,jnt,sicepat,pos,tiki,anteraja,wahana,ninja,lion')))
            ->map(fn ($courier) => trim(strtolower($courier)))
            ->map(fn ($courier) => str_replace(['j&t', 'jnt express'], ['jnt', 'jnt'], $courier))
            ->filter()
            ->unique()
            ->values();

        $combinedCourier = $couriers->implode(':');
        $options = collect();
        $usedDestinationId = null;
        $quotaLimit = false;

        foreach ($destinationIds as $destinationId) {
            $cacheKey = 'rajaongkir_rates_' . md5(implode('|', [$originId, $destinationId, $weight, $combinedCourier]));

            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                $options = collect($cached);
                $usedDestinationId = (int) $destinationId;
                break;
            }

            $endpoints = [
                'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost',
                'https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost',
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $payload = $this->rajaOngkirCostPayload((int) $originId, (int) $destinationId, $weight, $combinedCourier);
                    $response = $this->rajaOngkirRequest()->asForm()->post($endpoint, $payload);

                    if ($response->status() === 429) {
                        $quotaLimit = true;
                        Log::warning('RajaOngkir checkout quota limit', [
                            'step' => 'calculate-cost',
                            'endpoint' => $endpoint,
                            'destination_id' => $destinationId,
                            'status' => 429,
                            'body' => $response->body(),
                        ]);
                        break 2;
                    }

                    if (!$response->successful()) {
                        Log::warning('RajaOngkir checkout problem', [
                            'step' => 'calculate-cost',
                            'endpoint' => $endpoint,
                            'destination_id' => $destinationId,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                        continue;
                    }

                    $rates = $this->extractRajaOngkirRates($response->json(), $combinedCourier);
                    if (!empty($rates)) {
                        $options = collect($rates);
                        $usedDestinationId = (int) $destinationId;
                        Cache::put($cacheKey, $options->values()->all(), now()->addHours(6));
                        break 2;
                    }
                } catch (\Throwable $error) {
                    report($error);
                    continue;
                }
            }
        }

        $finalOptions = $options
            ->unique(fn ($item) => strtolower(($item['courier_code'] ?? '') . '|' . ($item['service'] ?? '') . '|' . ($item['cost'] ?? 0)))
            ->sortBy('cost')
            ->values();

        return response()->json([
            'ok' => $finalOptions->isNotEmpty(),
            'message' => $finalOptions->isNotEmpty()
                ? 'Opsi ongkir berhasil dimuat dari RajaOngkir Komerce.'
                : ($quotaLimit ? 'Kuota RajaOngkir hari ini sudah habis. Coba lagi setelah kuota reset.' : 'Belum ada ongkir tersedia untuk alamat/courier tersebut.'),
            'options' => $finalOptions,
            'destination_id' => $usedDestinationId,
        ], $finalOptions->isNotEmpty() ? 200 : 422);
    }

    public function applyPromo(Request $request)
    {
        $authUser = $this->authUser();

        if (!$authUser || empty($authUser['id'])) {
            return $this->loginResponse($request);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $promo = PromoCode::where('code', strtoupper(trim($data['code'])))
            ->where('is_active', true)
            ->first();

        if (!$promo) {
            return back()->withErrors(['code' => 'Promo code tidak ditemukan atau tidak aktif.']);
        }

        $summary = $this->cartSummary((int) $authUser['id']);
        if (!$promo->isValidFor((int) $summary['subtotal'])) {
            return back()->withErrors(['code' => 'Voucher belum memenuhi syarat pemakaian.']);
        }

        session([
            'glowskin_promo' => [
                'code' => $promo->code,
                'type' => $promo->discount_type ?? 'percent',
                'discount' => (int) $promo->discount_value,
            ],
        ]);

        return back()->with('success', 'Promo code berhasil dipakai.');
    }

    public function removePromo(Request $request)
    {
        if (!$this->authUser()) {
            return $this->loginResponse($request);
        }

        session()->forget('glowskin_promo');

        return back()->with('success', 'Promo code dihapus.');
    }
}
