<?php

namespace App\Services\WhatsApp;

use App\Http\Controllers\Shipxpeedapi\NdrController;
use App\Models\Order;
use App\Services\FshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a buyer's NDR choice (from the WhatsApp link) to the courier.
 * Only couriers whose NDR API is already integrated in the panel are pushed
 * automatically; everything else is queued for the ops team ("needs_manual").
 *
 * Actions: re-attempt | change-address | change-phone | rto
 */
class NdrActionDispatcher
{
    /**
     * @return array{status:string, message:string, response:mixed}
     *   status: sent_to_courier | needs_manual | failed
     */
    public function dispatch(Order $order, string $action, array $data): array
    {
        $courier = strtolower((string) $order->courier_id);

        try {
            if ($courier === 'fship') {
                $r = app(FshipService::class)->ndrAction($order, $action, $data);
                return $this->result((bool) ($r['status'] ?? false), $r['message'] ?? '', $r);
            }

            if ($action === 'rto') {
                return ['status' => 'needs_manual', 'message' => 'Return request: ops team to raise RTO with the courier.', 'response' => null];
            }

            if (in_array($courier, ['parcelx', 'delhivery 500gm'], true)) {
                // ParcelX REATTEMPT carries the (new) address + phone too
                $req = $this->request($order, $data);
                $r = app(NdrController::class)->parcelXNdrReattempt($req, $order->awb_number, $order->seller_id);
                $ok = ($r['http_status'] ?? 0) >= 200 && ($r['http_status'] ?? 0) < 300
                    && !in_array(($r['api_response']['status'] ?? true), [false, 'false', 0, '0'], true);
                return $this->result($ok, $ok ? 'Sent to courier' : 'Courier rejected the request', $r);
            }

            if (in_array($courier, ['boxd', 'delhivery 250gms'], true)) {
                if ($action !== 're-attempt') {
                    return ['status' => 'needs_manual', 'message' => 'This courier only accepts re-attempts by API; ops team to update address/phone.', 'response' => null];
                }
                $r = app(NdrController::class)->boxdNdrReattempt($this->request($order, $data), $order->seller_id);
                $ok = ($r['http_status'] ?? 0) >= 200 && ($r['http_status'] ?? 0) < 300
                    && !in_array(($r['api_response']['status'] ?? true), [false, 'false', 0, '0'], true);
                return $this->result($ok, $ok ? 'Sent to courier' : 'Courier rejected the request', $r);
            }

            if (str_starts_with($courier, 'delhivery_b2c')) {
                return $this->delhivery($order, $action, $data);
            }
        } catch (\Throwable $e) {
            Log::warning('NDR dispatch failed', ['order' => $order->id, 'courier' => $courier, 'error' => $e->getMessage()]);
            return ['status' => 'failed', 'message' => $e->getMessage(), 'response' => null];
        }

        return ['status' => 'needs_manual', 'message' => 'Courier has no NDR API in the panel yet; ops team to action.', 'response' => null];
    }

    /** Delhivery: details via /api/p/edit, then RE-ATTEMPT via /api/p/update. */
    private function delhivery(Order $order, string $action, array $data): array
    {
        $token = env('DELHIVERY_B2C_TOKEN') ?: env('DELHIVERY_API_TOKEN');
        if (!$token) {
            return ['status' => 'needs_manual', 'message' => 'Delhivery token not set in .env (DELHIVERY_B2C_TOKEN).', 'response' => null];
        }
        $http = Http::timeout(20)->acceptJson()->withHeaders(['Authorization' => 'Token ' . $token]);
        $log = [];

        if (in_array($action, ['change-address', 'change-phone'], true)) {
            $edit = array_filter([
                'waybill' => $order->awb_number,
                'add'     => $action === 'change-address' ? trim(($data['address'] ?? '') . ' ' . ($data['landmark'] ?? '')) : null,
                'phone'   => $action === 'change-phone' ? preg_replace('/\D/', '', (string) ($data['phone'] ?? '')) : null,
            ]);
            $r = $http->post('https://track.delhivery.com/api/p/edit', $edit);
            $log['edit'] = ['http' => $r->status(), 'body' => $r->json() ?? $r->body()];
            if (!$r->successful() || ($r->json('status') === false)) {
                return ['status' => 'failed', 'message' => 'Delhivery did not accept the new details', 'response' => $log];
            }
        }

        $r = $http->post('https://track.delhivery.com/api/p/update', ['data' => [['waybill' => $order->awb_number, 'act' => 'RE-ATTEMPT']]]);
        $log['update'] = ['http' => $r->status(), 'body' => $r->json() ?? $r->body()];
        $ok = $r->successful() && $r->json('status') !== false;
        return $this->result($ok, $ok ? 'Re-attempt requested with Delhivery' : 'Delhivery rejected the re-attempt', $log);
    }

    private function request(Order $order, array $data): Request
    {
        return new Request([
            'awb_number'     => $order->awb_number,
            'remark'         => $data['remark'] ?? 'Buyer requested via WhatsApp',
            'remarks'        => $data['remark'] ?? 'Buyer requested via WhatsApp',
            'address'        => $data['address'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'reattempt_date' => $data['reattempt_date'] ?? null,
            'rescheduleDate' => $data['reattempt_date'] ?? null,
        ]);
    }

    private function result(bool $ok, string $message, $response): array
    {
        return ['status' => $ok ? 'sent_to_courier' : 'failed', 'message' => $message, 'response' => $response];
    }
}
