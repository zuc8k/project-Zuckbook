<?php
/**
 * Helper Functions for ZuckBook
 */

/**
 * Format large numbers to short format (K, M, B)
 * Examples: 1000 -> 1K, 1500 -> 1.5K, 1000000 -> 1M
 * 
 * @param int|float $number The number to format
 * @param int $precision Decimal precision (default: 1)
 * @return string Formatted number
 */
function formatNumber($number, $precision = 1) {
    if ($number < 1000) {
        return number_format($number);
    } elseif ($number < 1000000) {
        // Thousands (K)
        $formatted = $number / 1000;
        return number_format($formatted, $precision) . 'K';
    } elseif ($number < 1000000000) {
        // Millions (M)
        $formatted = $number / 1000000;
        return number_format($formatted, $precision) . 'M';
    } else {
        // Billions (B)
        $formatted = $number / 1000000000;
        return number_format($formatted, $precision) . 'B';
    }
}

/**
 * Format number for Arabic display
 * Same as formatNumber but with Arabic letters
 */
function formatNumberAr($number, $precision = 1) {
    if ($number < 1000) {
        return number_format($number);
    } elseif ($number < 1000000) {
        // Thousands (ألف)
        $formatted = $number / 1000;
        return number_format($formatted, $precision) . ' ألف';
    } elseif ($number < 1000000000) {
        // Millions (مليون)
        $formatted = $number / 1000000;
        return number_format($formatted, $precision) . ' مليون';
    } else {
        // Billions (مليار)
        $formatted = $number / 1000000000;
        return number_format($formatted, $precision) . ' مليار';
    }
}

/**
 * Format number based on current language
 */
function formatNumberLang($number, $precision = 1) {
    $lang = $_SESSION['lang'] ?? 'en';
    
    if ($lang === 'ar') {
        return formatNumberAr($number, $precision);
    } else {
        return formatNumber($number, $precision);
    }
}

/**
 * Format coins display
 */
function formatCoins($coins) {
    return formatNumber($coins, 1);
}

/**
 * Format followers/friends count
 */
function formatCount($count) {
    return formatNumber($count, 1);
}
?>
