<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One weight discrepancy on one AWB.
 *
 * dispute_status:
 *   charged          excess debited; seller must Accept it or Reject it with proof before dispute_deadline
 *   seller_accepted  seller accepted the charge; closed (raised_at = when the seller acted)
 *   disputed         seller rejected the charge with proof; waiting for admin review
 *   accepted  admin accepted; excess refunded to the wallet
 *   rejected  admin rejected; closed, charge stays
 *   closed    dispute window passed without a dispute (charge final)   [also: old charged rows]
 *   void      old never-charged rows from the previous screen (hidden)
 */
class WeightDisputes extends Model
{
    use HasFactory;

    protected $table = 'weight_disputes';

    public const CHARGED = 'charged';
    public const DISPUTED = 'disputed';
    public const SELLER_ACCEPTED = 'seller_accepted';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const CLOSED = 'closed';
    public const VOID = 'void';

    protected $fillable = [
        'awb', 'courier', 'Mentionedweight', 'chargedweight', 'weightmissmatched', 'weightdisputecharges', 'status',
        'seller_id', 'order_id', 'order_number', 'applied_weight', 'applied_dead_weight', 'applied_vol_weight',
        'charged_weight_g', 'weight_type', 'courier_length', 'courier_breadth', 'courier_height',
        'extra_slabs', 'excess_charge', 'calc_method', 'admin_note', 'dispute_status', 'deducted_at',
        'dispute_deadline', 'debit_recharge_id', 'seller_remark', 'seller_proofs', 'raised_at',
        'admin_remark', 'reviewed_at', 'reviewed_by', 'refund_recharge_id', 'refunded_at', 'upload_batch',
        'source', 'courier_proofs', 'seller_links',
    ];

    protected $casts = [
        'seller_proofs' => 'array',
        'courier_proofs' => 'array',
        'seller_links' => 'array',
        'deducted_at' => 'datetime',
        'dispute_deadline' => 'datetime',
        'raised_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /** Hours after the deduction during which the seller can raise a dispute. */
    public static function windowHours(): int
    {
        return max(1, (int) env('WEIGHT_DISPUTE_WINDOW_HOURS', 24));
    }

    /** Status as it stands right now (a 'charged' row past its deadline reads as 'closed'). */
    public function liveStatus(): string
    {
        $s = $this->dispute_status ?: self::VOID;
        if ($s === self::CHARGED && $this->dispute_deadline && $this->dispute_deadline->isPast()) {
            return self::CLOSED;
        }
        return $s;
    }

    public function canRaise(): bool
    {
        return $this->dispute_status === self::CHARGED
            && $this->dispute_deadline
            && $this->dispute_deadline->isFuture();
    }

    /** Why the seller can no longer respond (for error messages). */
    public function lockedReason(): string
    {
        if ($this->liveStatus() === self::CLOSED) {
            return 'The ' . self::windowHours() . '-hour window to respond to this charge has ended, so the charge is final.';
        }
        return 'You have already responded to this charge (' . self::statusLabel($this->liveStatus()) . ').';
    }

    public static function statusLabel(string $s, bool $forAdmin = false): string
    {
        return [
            self::CHARGED => $forAdmin ? 'Awaiting seller' : 'Action needed',
            self::SELLER_ACCEPTED => $forAdmin ? 'Seller accepted' : 'You accepted · Closed',
            self::DISPUTED => 'Under review',
            self::ACCEPTED => $forAdmin ? 'Accepted · Refunded' : 'Dispute won · Refunded',
            self::REJECTED => $forAdmin ? 'Rejected · Closed' : 'Dispute rejected · Closed',
            self::CLOSED => 'Closed',
            self::VOID => 'Not charged',
        ][$s] ?? ucfirst($s);
    }

    private static function proofCount(array $proofs): string
    {
        $v = count(array_filter($proofs, fn ($p) => ($p['type'] ?? '') === 'video'));
        $i = count($proofs) - $v;
        $parts = [];
        if ($i) {
            $parts[] = $i . ' photo' . ($i > 1 ? 's' : '');
        }
        if ($v) {
            $parts[] = $v . ' video';
        }
        return $parts ? implode(' + ', $parts) : 'no files';
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function seller()
    {
        return $this->belongsTo(SellerList::class, 'seller_id');
    }

    /** Everything the seller / admin detail view needs, as plain data. */
    public function detail(bool $forAdmin = false): array
    {
        $kg = fn ($g) => $g === null ? null : round(((float) $g) / 1000, 3);
        $s = $this->liveStatus();
        $proofs = [];
        foreach ((array) $this->seller_proofs as $i => $p) {
            $proofs[] = [
                'name' => $p['name'] ?? ('proof-' . ($i + 1)),
                'type' => $p['type'] ?? 'image',
                'size' => $p['size'] ?? null,
                'url' => route($forAdmin ? 'weight.dispute.proof' : 'seller.weight.dispute.proof', [$this->id, $i]),
            ];
        }
        $timeline = [];
        if ($this->deducted_at) {
            $timeline[] = ['at' => $this->deducted_at->format('d M Y, h:i A'), 'text' => ($this->source === 'auto' ? 'Courier re-weighed the parcel (fetched automatically) — ' : '') . '₹' . number_format((float) $this->excess_charge, 2) . ' excess weight charge debited from wallet'];
        }
        if ($this->raised_at) {
            $nLinks = count((array) $this->seller_links);
            $timeline[] = ['at' => $this->raised_at->format('d M Y, h:i A'), 'text' => $this->dispute_status === self::SELLER_ACCEPTED
                ? 'Charge accepted by seller — case closed'
                : 'Charge rejected by seller — dispute raised with ' . self::proofCount($proofs) . ($nLinks ? ' and ' . $nLinks . ' link(s)' : '')];
        }
        if ($this->reviewed_at) {
            $timeline[] = ['at' => $this->reviewed_at->format('d M Y, h:i A'), 'text' => $this->dispute_status === self::ACCEPTED
                ? 'Dispute accepted — ₹' . number_format((float) $this->excess_charge, 2) . ' refunded to wallet'
                : 'Dispute rejected — case closed'];
        }
        if ($s === self::CLOSED && !$this->raised_at && $this->dispute_deadline) {
            $timeline[] = ['at' => $this->dispute_deadline->format('d M Y, h:i A'), 'text' => 'Dispute window ended — charge final'];
        }

        // rows from the old screen only have the free-text columns
        $legacy = $this->applied_weight === null && $this->charged_weight_g === null;
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $dim = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        return [
            'id' => $this->id,
            'awb' => $this->awb,
            'order_number' => $this->order_number,
            'courier' => $this->courier ?: '—',
            'status' => $s,
            'status_label' => self::statusLabel($s, $forAdmin),
            'applied_kg' => $legacy ? $num($this->Mentionedweight) : $kg($this->applied_weight),
            'applied_dead_kg' => $kg($this->applied_dead_weight),
            'applied_vol_kg' => $kg($this->applied_vol_weight),
            'charged_kg' => $legacy ? $num($this->chargedweight) : $kg($this->charged_weight_g),
            'diff_kg' => $this->charged_weight_g !== null && $this->applied_weight !== null
                ? round(((float) $this->charged_weight_g - (float) $this->applied_weight) / 1000, 3) : null,
            'weight_type' => $this->weight_type,
            'courier_dims' => $this->courier_length ? implode(' × ', array_map($dim, [$this->courier_length, $this->courier_breadth, $this->courier_height])) . ' cm' : null,
            'extra_slabs' => $this->extra_slabs,
            'excess_charge' => (float) ($legacy ? $num($this->weightdisputecharges) : $this->excess_charge),
            'admin_note' => $this->admin_note,
            'deducted_at' => optional($this->deducted_at)->format('d M Y, h:i A'),
            'deadline' => optional($this->dispute_deadline)->format('d M Y, h:i A'),
            'deadline_iso' => optional($this->dispute_deadline)->toIso8601String(),
            'can_raise' => $this->canRaise(),
            'seller_remark' => $this->seller_remark,
            'admin_remark' => $this->admin_remark,
            'proofs' => $proofs,
            'source' => $this->source ?: 'excel',
            'courier_proofs' => self::cleanLinks((array) $this->courier_proofs),
            'seller_links' => self::cleanLinks((array) $this->seller_links),
            'timeline' => $timeline,
        ];
    }

    /** Keep only http(s) links; each as ['url', 'kind' => image|video|link, 'label']. */
    public static function cleanLinks(array $list): array
    {
        $out = [];
        foreach ($list as $item) {
            $url = is_array($item) ? ($item['url'] ?? '') : (string) $item;
            $url = trim($url);
            if (!preg_match('~^https?://~i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $path = strtolower((string) parse_url($url, PHP_URL_PATH));
            $kind = preg_match('/\.(jpe?g|png|webp|gif|heic)$/', $path) ? 'image'
                : (preg_match('/\.(mp4|mov|webm|3gp|mkv)$/', $path) ? 'video' : 'link');
            $out[] = [
                'url' => $url,
                'kind' => is_array($item) && !empty($item['kind']) ? $item['kind'] : $kind,
                'label' => is_array($item) && !empty($item['label']) ? (string) $item['label'] : (string) parse_url($url, PHP_URL_HOST),
            ];
        }
        return array_slice($out, 0, 10);
    }
}
