<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\CartItem;
use App\Models\ProductPurchase;
use App\Support\DatabaseColumn;

class CheckoutOrder extends Model
{
    protected $fillable = [
        'user_id',
        'order_code',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'payment_status',
        'payment_type',
        'subtotal',
        'discount',
        'shipping_cost',
        'grand_total',
        'shipping_courier',
        'shipping_service',
        'shipping_etd',
        'shipping_description',
        'recipient_name',
        'phone',
        'address_line',
        'district',
        'city',
        'province',
        'postal_code',
        'map_link',
        'courier_note',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'shipping_cost' => 'integer',
        'grand_total' => 'integer',
        'paid_at' => 'datetime',
    ];


    public static function normalizePaymentStatus(?string $rawStatus): string
    {
        $status = strtolower(trim((string) $rawStatus));

        $paidStatuses = ['capture', 'settlement', 'success', 'paid'];
        $failedStatuses = ['deny', 'denied', 'cancel', 'cancelled', 'expire', 'expired', 'failure', 'failed'];

        if (in_array($status, $paidStatuses, true)) {
            return 'paid';
        }

        if (in_array($status, $failedStatuses, true)) {
            return 'failed';
        }

        return 'pending';
    }

    public function applyMidtransStatus(?string $rawStatus, array $payload = []): self
    {
        $targetStatus = self::normalizePaymentStatus($rawStatus);
        $wasPaid = $this->payment_status === 'paid';

        DB::transaction(function () use ($targetStatus, $payload, $wasPaid) {
            $this->update([
                'payment_status' => $targetStatus,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $payload['transaction_id'] ?? $this->midtrans_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $this->payment_type,
                'paid_at' => $targetStatus === 'paid' ? ($this->paid_at ?: now()) : $this->paid_at,
            ]);

            if ($targetStatus === 'paid' && !$wasPaid) {
                $this->loadMissing('items.product');

                foreach ($this->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    $purchase = ProductPurchase::firstOrNew([
                        'user_id' => $this->user_id,
                        'product_id' => $item->product_id,
                    ]);

                    $purchase->quantity = max((int) ($purchase->quantity ?? 0), (int) $item->quantity);
                    $purchase->status = 'paid';
                    $purchase->purchased_at = now();
                    $purchase->save();

                    if ($item->product && DatabaseColumn::has('products', 'sold_count')) {
                        $item->product->increment('sold_count', (int) $item->quantity);
                    }
                }

                CartItem::where('user_id', $this->user_id)->delete();
            }
        });

        return $this->fresh('items.product');
    }

    public function syncStatusFromMidtrans(): self
    {
        if (!$this->midtrans_order_id || !$this->exists) {
            return $this;
        }

        $serverKey = trim((string) env('MIDTRANS_SERVER_KEY'));
        if ($serverKey === '') {
            return $this;
        }

        $isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
        $endpoint = ($isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com')
            . '/v2/' . rawurlencode($this->midtrans_order_id) . '/status';

        try {
            $http = Http::timeout(20)
                ->connectTimeout(10)
                ->withBasicAuth($serverKey, '')
                ->acceptJson();

            if (!filter_var(env('MIDTRANS_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) || app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->get($endpoint);

            if (!$response->successful()) {
                report(new \RuntimeException('Midtrans status check error [' . $response->status() . ']: ' . $response->body()));
                return $this;
            }

            $data = $response->json() ?: [];
            $rawStatus = $data['transaction_status'] ?? $data['status'] ?? $this->payment_status;

            return $this->applyMidtransStatus($rawStatus, [
                'transaction_id' => $data['transaction_id'] ?? $this->midtrans_transaction_id,
                'payment_type' => $data['payment_type'] ?? $this->payment_type,
            ]);
        } catch (\Throwable $error) {
            report($error);
            return $this;
        }
    }

    public function items()
    {
        return $this->hasMany(CheckoutOrderItem::class, 'checkout_order_id');
    }
}
