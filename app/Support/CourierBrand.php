<?php

namespace App\Support;

/**
 * Seller-facing courier branding.
 *
 * Sellers must only ever see the real courier (Delhivery, Blue Dart, Ekart ...),
 * never the aggregator account we book through (Shipclues, Fship, Shiprocket ...).
 * Everything that puts a courier name or logo in front of a seller goes through here.
 */
class CourierBrand
{
    /** Neutral logo used when a courier has no known brand image. */
    public const DEFAULT_LOGO = 'brands/courier-default.svg';

    /** Aggregator / reseller accounts whose own name and logo must stay hidden from sellers. */
    private const AGGREGATORS = [
        'shipclues', 'fship', 'shiprocket', 'smartship', 'tekipost', 'boxd',
        'parcelx', 'selloship', 'bigship', 'nimbuspost', 'ithink', 'shipway',
    ];

    /** Words removed from any courier name shown to sellers. */
    private const HIDDEN_WORDS = ['shipclues', 'ship clues', 'fship'];

    public static function isAggregator($provider): bool
    {
        if (!$provider) {
            return false;
        }
        $code = strtolower(trim((string) ($provider->code ?? '')));
        $name = strtolower(trim((string) ($provider->name ?? '')));

        foreach (self::AGGREGATORS as $agg) {
            if ($code === $agg || $name === $agg || str_contains($code, $agg) || str_contains($name, $agg)) {
                return true;
            }
        }
        return false;
    }

    /** Local brand image for a courier, matched on the courier name. Null when unknown. */
    public static function logo(?string $courierName): ?string
    {
        $n = strtolower((string) $courierName);
        if ($n === '') {
            return null;
        }

        return match (true) {
            str_contains($n, 'delhivery') || str_contains($n, 'deliveri') => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
            str_contains($n, 'bluedart') || str_contains($n, 'blue dart') || str_contains($n, 'blue_dart') => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
            str_contains($n, 'xpressbee') || str_contains($n, 'xpress bee') || preg_match('/\bxb\b/', $n) === 1 => 'brands/Xpressbee.png',
            str_contains($n, 'ekart') || str_contains($n, 'e-kart') || str_contains($n, 'flipkart') => 'brands/ecart.png',
            str_contains($n, 'dtdc') => 'brands/1750337596_images-removebg-preview.png',
            str_contains($n, 'amazon') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
            str_contains($n, 'shadowfax') => 'brands/1749459823_shadowfax_wedeliver (1).png',
            str_contains($n, 'maruti') => 'brands/Shreemaruti.jpg',
            default => null,
        };
    }

    /** Logo to show a seller: known brand image, else a safe hint, else the neutral default. */
    public static function sellerLogo(?string $courierName, ?string $hint = null): string
    {
        return self::logo($courierName)
            ?? (self::isSafeLogo($hint) ? $hint : null)
            ?? self::DEFAULT_LOGO;
    }

    /** Courier name with any aggregator wording stripped. Never returns an aggregator name. */
    public static function cleanName(?string $name, string $fallback = 'Courier'): string
    {
        $clean = (string) $name;
        foreach (self::HIDDEN_WORDS as $word) {
            $clean = preg_replace('/' . preg_quote($word, '/') . '/i', '', $clean);
        }
        $clean = trim(preg_replace('/\s{2,}/', ' ', $clean), " \t\n\r\0\x0B-_|/:");

        return $clean !== '' ? $clean : $fallback;
    }

    /** A hint is safe when it is a local file that does not name an aggregator. */
    private static function isSafeLogo(?string $path): bool
    {
        if (!$path || str_starts_with($path, 'http')) {
            return false;
        }
        $p = strtolower($path);
        foreach (self::AGGREGATORS as $agg) {
            if (str_contains($p, $agg)) {
                return false;
            }
        }
        return is_file(public_path($path));
    }
}
