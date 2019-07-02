<?php
/*
Plugin Name: WooCommerce Veloyd
Author: Veloyd
Version: 1.0.2
Text Domain: woocommerce-veloyd

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

require '/plugin-update-checker-4.6/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/Veloyd/woo-veloyd',
	__FILE__,
	'woocommerce-veloyd'
);

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	if ( is_admin() ){ // admin actions
	  add_action('admin_menu', 'veloyd_register_settings_page');
	  add_action( 'admin_init', 'register_mysettings' );
	}

	function register_mysettings() { // whitelist options
	  register_setting( 'veloyd-settings', 'veloyd_api_key' );
	}

	function veloyd_register_settings_page() {
		add_submenu_page('woocommerce',	'WooCommerce Veloyd Settings', 'Veloyd', 'manage_options', 'woocommerce_veloyd_settings', 'veloyd_settings_page');
	}

	function veloyd_settings_page() {
		?>
		    <div>
				<div class="wrap">
				<h1>WooCommerce Veloyd Settings</h1>
			    <form method="post" action="options.php">
			    <?php settings_fields( 'veloyd-settings' ); ?>
			    <?php do_settings_sections( 'veloyd-settings' ); ?>

			    <table>
				    <tr valign="top">
					    <th scope="row">
							<label for="veloyd_api_key">API key</label>
						</th>
					    <td>
							<input type="text" id="veloyd_api_key" name="veloyd_api_key" value="<?php echo get_option('veloyd_api_key'); ?>" />
						</td>
				    </tr>
			    </table>
			    <?php  submit_button(); ?>
			    </form>
		    </div>
  		<?php
	}

	add_action( 'woocommerce_new_order', 'sendToVeloyd' );

	function sendToVeloyd($order_id) {

		$api_key = get_option('veloyd_api_key');

		if(!!$api_key) {
			$order = new WC_Order($order_id);

	        $json = Array(
	            "parcel" => Array(
	                "address" => Array(
	                    "name" => empty($order->get_shipping_company()) ? $order->get_shipping_first_name() . " " . $order->get_shipping_last_name() : $order->get_shipping_company(),
	                    "attention" => empty($order->get_shipping_company()) ? "" : $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
	                    "postalCode" => $order->get_shipping_postcode(),
	                    "nr" => $order->get_meta('_shipping_house_number'),
	                    "addition" => $order->get_meta('_shipping_house_number_suffix'),
	                    "street" => empty($order->get_meta('_shipping_street_name')) ? $order->get_shipping_address_1() : $order->get_meta('_shipping_street_name'),
	                    "street2" => $order->get_shipping_address_2(),
	                    "city" => $order->get_shipping_city(),
	                    "country" => $order->get_shipping_country()
	                ),
	            "emailTT" => $order->get_billing_email(),
                "reference" => $order->get_order_number(),
	            "options" => Array()
                ),
                "plugin" => "WooCommerce 1.0.2"
	        );

	        $curl = curl_init();

	        curl_setopt_array($curl, array(
	            CURLOPT_URL => "https://app.veloyd.nl/api/parcel/create",
	            CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_ENCODING => "",
	            CURLOPT_MAXREDIRS => 10,
	            CURLOPT_TIMEOUT => 30,
	            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	            CURLOPT_CUSTOMREQUEST => "POST",
	            CURLOPT_POSTFIELDS => json_encode($json),
	            CURLOPT_HTTPHEADER => array(
	                "authorization: Apikey " . $api_key,
	                "content-type: application/json"
	            ),
	        ));

	        $response = curl_exec($curl);
	        $err = curl_error($curl);
	        curl_close($curl);

	        if ($err) {
	            error_log($err);
	        } else {
	            error_log($response);
	        }
		} else{
			error_log('No API key provided');
		}
    }
}
