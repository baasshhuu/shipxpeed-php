<?php

namespace App\Support\Label;

/**
 * Self-contained Code 128 barcode generator (no network, no composer package).
 * Uses Code Set C for all-digit runs (compact, scans better for numeric AWBs)
 * and Code Set B otherwise. Output is an SVG string or a data: URI that
 * dompdf and browsers both render.
 */
class Code128
{
    private const PATTERNS = [
        '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213',
        '221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
        '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211',
        '212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
        '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331',
        '231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
        '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214',
        '112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
        '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141',
        '214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
        '114131','311141','411131','211412','211214','211232','2331112',
    ];

    private const START_B = 104;
    private const START_C = 105;
    private const CODE_B  = 100;
    private const CODE_C  = 99;
    private const STOP    = 106;

    /** @return int[] symbol values incl. start, checksum and stop */
    public static function encode(string $text): array
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        if ($text === '') {
            $text = '0';
        }

        $codes = [];
        $len = strlen($text);
        $i = 0;
        $set = null;

        while ($i < $len) {
            // count digit run from here
            $run = 0;
            while ($i + $run < $len && ctype_digit($text[$i + $run])) {
                $run++;
            }
            $useC = $run >= 4 || ($run >= 2 && $run === $len - $i && $set === null);
            if ($useC) {
                $pairs = intdiv($run, 2);
                if ($set === null) {
                    $codes[] = self::START_C;
                } elseif ($set !== 'C') {
                    $codes[] = self::CODE_C;
                }
                $set = 'C';
                for ($p = 0; $p < $pairs; $p++) {
                    $codes[] = (int) substr($text, $i, 2);
                    $i += 2;
                }
                continue;
            }
            if ($set === null) {
                $codes[] = self::START_B;
            } elseif ($set !== 'B') {
                $codes[] = self::CODE_B;
            }
            $set = 'B';
            $codes[] = ord($text[$i]) - 32;
            $i++;
        }

        $sum = $codes[0];
        for ($k = 1, $n = count($codes); $k < $n; $k++) {
            $sum += $codes[$k] * $k;
        }
        $codes[] = $sum % 103;
        $codes[] = self::STOP;

        return $codes;
    }

    /** Bar/space module widths, alternating bar, space, bar ... */
    public static function modules(string $text): array
    {
        $widths = [];
        foreach (self::encode($text) as $code) {
            foreach (str_split(self::PATTERNS[$code]) as $w) {
                $widths[] = (int) $w;
            }
        }
        return $widths;
    }

    public static function svg(string $text, int $height = 60, float $module = 2.0, int $quiet = 10): string
    {
        $mods = self::modules($text);
        $total = array_sum($mods) + 2 * $quiet;
        $w = $total * $module;
        $x = $quiet * $module;
        $rects = '';
        foreach ($mods as $idx => $m) {
            $mw = $m * $module;
            if ($idx % 2 === 0) {
                $rects .= sprintf('<rect x="%.2f" y="0" width="%.2f" height="%d"/>', $x, $mw, $height);
            }
            $x += $mw;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%.2f" height="%d" viewBox="0 0 %.2f %d" preserveAspectRatio="none">'
            . '<rect width="100%%" height="100%%" fill="#fff"/><g fill="#000">%s</g></svg>',
            $w, $height, $w, $height, $rects
        );
    }

    public static function dataUri(string $text, int $height = 60, float $module = 2.0): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($text, $height, $module));
    }
}
