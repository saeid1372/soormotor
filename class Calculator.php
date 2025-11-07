<?php
/**
 * Calculator Class.
 *
 * Handles all calculation logic for installments and discounts.
 * This class is designed to be stateless and reusable.
 *
 * @package Soorenamotor
 */

namespace Soorenamotor;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Calculator {

    /**
     * Calculate the weighted average repayment period for a given cart in DAYS.
     *
     * @param \WC_Cart $cart The cart object to calculate for.
     * @return float The average number of days.
     */
    public function calc_repayment_period( \WC_Cart $cart ): float {
        if ( $cart->is_empty() ) {
            return 0.0;
        }

        $total_price = 0.0;
        $weighted_sum = 0.0;

        foreach ( $cart->get_cart() as $cart_item ) {
            $line_total   = (float) $cart_item['line_total'] + (float) $cart_item['line_tax'];
            // فرض بر این است که _installments_number اکنون تعداد روزها را ذخیره می‌کند.
            $installments_in_days = (int) $cart_item['data']->get_meta( '_installments_number', true );

            $total_price   += $line_total;
            $weighted_sum += $line_total * $installments_in_days;
        }

        $average_days = $total_price > 0 ? ( $weighted_sum / $total_price )*30 : 0.0;
        
        
        
        return $average_days;
    }

    /**
     * Calculate the cash discount amount based on the repayment period in days.
     * A discount of 0.1% is applied for each day over a 20-day threshold.
     *
     * @param float $average_days The average repayment period in days.
     * @param float $cart_total The total cart value before discount.
     * @return float The discount amount (negative value).
     */
    public function get_cash_discount_amount( float $average_days, float $cart_total ): float {
        if ( $average_days < 1 ) {
            return 0.0;
        }

        $whole_days = intval( $average_days );

        // اگر دوره بازپرداخت ۲۰ روز یا کمتر بود، تخفیفی اعمال نمی‌شود.
        if ( $whole_days <= 20 ) {
            return 0.0;
        }

       // به ازای هر روز از دوره بازپرداخت، ۰.۱ درصد تخفیف اعمال می‌شود.
       $discount_percent = $whole_days * 0.1;

    
        $discount_amount = $cart_total * $discount_percent / 100;

        return -round( $discount_amount, wc_get_price_decimals() );
    }
}

