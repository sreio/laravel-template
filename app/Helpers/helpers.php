<?php

if (! function_exists('format_volume')) {
    /**
     * Format volume.
     *
     * @param $number
     * @param int $precision
     * @return string
     */
    function format_volume($number, int $precision = 2): string
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, $precision) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, $precision) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, $precision) . 'K';
        }
        return $number;
    }
}

