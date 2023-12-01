<?php
/**
 * Plugin Name:       Additional Kit on Checkout
 * Plugin URI:        http://www.finaldatasolutions.com/
 * Description:       This plugin will add the additional kit on the checkout page.
 * Version:           3.2.0
 * Author:            Ibrar Ayoub
 * Author URI:        http://www.finaldatasolutions.com/
 */



require 'plugin-update-checker-master/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/manager-wiseTech/woocommerce-additional-checkout-kit/',
	__FILE__,
	'woocommerce-additional-checkout-kit'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('your-token-here');


// Display the custom checkbow field in checkout
add_action( 'woocommerce_review_order_before_order_total', 'fee_installment_checkbox_field', 20 );
function fee_installment_checkbox_field(){
    echo '<tr class="packing-select"><th>';

    woocommerce_form_field( 'installment_fee', array(
        'type'          => 'checkbox',
        'class'         => array('installment-fee form-row-wide'),
        'label'         => __('First Aid Kit ($49.99)'),
        'placeholder'   => __(''),
    ), WC()->session->get('installment_fee') ? '1' : '' );

    echo '</th><td>';
}

// jQuery - Ajax script
add_action( 'wp_footer', 'checkout_fee_script' );
function checkout_fee_script() {
    // Only on Checkout
    if( is_checkout() && ! is_wc_endpoint_url() ) :

    if( WC()->session->__isset('installment_fee') )
        WC()->session->__unset('installment_fee')
    ?>
    <style type="text/css">
        .installment-fee input[type="checkbox"]{
            width: auto;
        }
    </style>
    <script type="text/javascript">
    jQuery( function($){
        if (typeof wc_checkout_params === 'undefined')
            return false;

        $('form.checkout').on('change', 'input[name=installment_fee]', function(){
            var fee = $(this).prop('checked') === true ? '1' : '';

            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                    'action': 'installment_fee',
                    'installment_fee': fee,
                },
                success: function (result) {
                    $('body').trigger('update_checkout');
                },
            });
        });
    });
    </script>
    <?php
    endif;
}

// Get Ajax request and saving to WC session
add_action( 'wp_ajax_installment_fee', 'get_installment_fee' );
add_action( 'wp_ajax_nopriv_installment_fee', 'get_installment_fee' );
function get_installment_fee() {
    if ( isset($_POST['installment_fee']) ) {
        WC()->session->set('installment_fee', ($_POST['installment_fee'] ? true : false) );
    }
    die();
}


// Add a custom calculated fee conditionally
add_action( 'woocommerce_cart_calculate_fees', 'set_installment_fee' );
function set_installment_fee( $cart ){
    if ( is_admin() && ! defined('DOING_AJAX') || ! is_checkout() )
        return;

    if ( did_action('woocommerce_cart_calculate_fees') >= 2 )
        return;

    if ( 1 == WC()->session->get('installment_fee') ) {
        $items_count = WC()->cart->get_cart_contents_count();
        $fee_label   = sprintf( __( "Additional First Aid Kit %s %s" ), '&times;', $items_count );
        $fee_amount  = 49.99 * $items_count;
        WC()->cart->add_fee( $fee_label, $fee_amount );
    }
}

add_filter( 'woocommerce_form_field' , 'remove_optional_txt_from_installment_checkbox', 10, 4 );
function remove_optional_txt_from_installment_checkbox( $field, $key, $args, $value ) {
    // Only on checkout page for Order notes field
    if( 'installment_fee' === $key && is_checkout() ) {
        $optional = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
        $field = str_replace( $optional, '', $field );
    }
    return $field;
}
?>
