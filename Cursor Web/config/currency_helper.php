<?php
/**
 * Currency Helper Functions for Indian Rupee (₹) formatting
 * Ensures consistent currency display throughout the application
 */

/**
 * Format amount in Indian Rupees with proper symbol and formatting
 * @param float $amount The amount to format
 * @param int $decimals Number of decimal places (default: 2)
 * @param bool $show_symbol Whether to show the ₹ symbol (default: true)
 * @return string Formatted currency string
 */
function format_inr($amount, $decimals = 2, $show_symbol = true) {
    $formatted = number_format((float)$amount, $decimals);
    return $show_symbol ? '₹' . $formatted : $formatted;
}

/**
 * Format amount for Indian number system (lakhs, crores)
 * @param float $amount The amount to format
 * @param bool $show_symbol Whether to show the ₹ symbol (default: true)
 * @return string Formatted currency string with Indian number system
 */
function format_inr_indian($amount, $show_symbol = true) {
    $amount = (float)$amount;
    $symbol = $show_symbol ? '₹' : '';
    
    if ($amount >= 10000000) { // 1 crore
        return $symbol . number_format($amount / 10000000, 2) . ' Cr';
    } elseif ($amount >= 100000) { // 1 lakh
        return $symbol . number_format($amount / 100000, 2) . ' L';
    } elseif ($amount >= 1000) { // 1 thousand
        return $symbol . number_format($amount / 1000, 2) . 'K';
    } else {
        return $symbol . number_format($amount, 2);
    }
}

/**
 * Convert amount to words in Indian Rupees
 * @param float $amount The amount to convert
 * @return string Amount in words
 */
function amount_to_words_inr($amount) {
    $amount = (float)$amount;
    $rupees = floor($amount);
    $paise = round(($amount - $rupees) * 100);
    
    $words = number_to_words($rupees) . ' Rupees';
    if ($paise > 0) {
        $words .= ' and ' . number_to_words($paise) . ' Paise';
    }
    
    return $words . ' Only';
}

/**
 * Helper function to convert numbers to words
 * @param int $number The number to convert
 * @return string Number in words
 */
function number_to_words($number) {
    $ones = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    );
    
    $tens = array(
        0 => '', 1 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    );
    
    if ($number == 0) return 'Zero';
    
    $words = '';
    
    // Crores
    if ($number >= 10000000) {
        $crores = floor($number / 10000000);
        $words .= number_to_words($crores) . ' Crore ';
        $number %= 10000000;
    }
    
    // Lakhs
    if ($number >= 100000) {
        $lakhs = floor($number / 100000);
        $words .= number_to_words($lakhs) . ' Lakh ';
        $number %= 100000;
    }
    
    // Thousands
    if ($number >= 1000) {
        $thousands = floor($number / 1000);
        $words .= number_to_words($thousands) . ' Thousand ';
        $number %= 1000;
    }
    
    // Hundreds
    if ($number >= 100) {
        $hundreds = floor($number / 100);
        $words .= $ones[$hundreds] . ' Hundred ';
        $number %= 100;
    }
    
    // Tens and ones
    if ($number >= 20) {
        $words .= $tens[floor($number / 10)] . ' ';
        $number %= 10;
    }
    
    if ($number > 0) {
        $words .= $ones[$number] . ' ';
    }
    
    return trim($words);
}

/**
 * JavaScript function to format currency on client side
 * @return string JavaScript function as string
 */
function get_currency_js_formatter() {
    return "
    function formatINR(amount, showSymbol = true) {
        const formatted = parseFloat(amount).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return showSymbol ? '₹' + formatted : formatted;
    }
    
    function formatINRShort(amount, showSymbol = true) {
        const num = parseFloat(amount);
        const symbol = showSymbol ? '₹' : '';
        
        if (num >= 10000000) {
            return symbol + (num / 10000000).toFixed(2) + ' Cr';
        } else if (num >= 100000) {
            return symbol + (num / 100000).toFixed(2) + ' L';
        } else if (num >= 1000) {
            return symbol + (num / 1000).toFixed(2) + 'K';
        } else {
            return symbol + num.toFixed(2);
        }
    }
    ";
}
?>