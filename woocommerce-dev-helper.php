<?php
/**
 * Plugin Name: A WooCommerce Dev Helper
 * Plugin URI: https://github.com/skyverge/woocommerce-dev-helper/
 * Description: A simple plugin for helping develop/debug WooCommerce & extensions
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.1.0
 * Text Domain: woocommerce-dev-helper
 * Domain Path: /i18n/languages/
 * WC requires at least: 1.0
 * WC tested up to: 8.0
 *
 * Copyright: (c) 2015-2021 SkyVerge [info@skyverge.com]
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WooCommerce-Dev-Helper
 * @author    SkyVerge
 * @category  Development
 * @copyright Copyright (c) 2012-2021, SkyVerge
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\DevHelper;

defined( 'ABSPATH' ) or exit;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

class Plugin {


	const VERSION = '1.1.0';


	/** @var Plugin instance */
	protected static $instance;

	/** @var Ajax instance */
	protected $ajax;

	/** @var Forwarded_URLs instance */
	protected $use_forwarded_urls;

	/** @var Integrations\Subscriptions instance */
	protected $subscriptions;

	/** @var Integrations\Memberships instance */
	protected $memberships;

	/** @var Bogus_Gateway instance */
	protected $gateway;

	/** @var Bogus_Gateway_Blocks_Support instance */
	protected $block_gateway;

	/**
	 * Bootstrap class
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// global functions
		require_once( $this->get_plugin_path() . '/includes/Functions.php' );

		// remove woo updater notice
		add_action( 'admin_init', array( $this, 'muzzle_woo_updater' ) );

		// class includes
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// maybe log actions/filters
		add_action( 'shutdown', array( $this, 'maybe_log_hooks' ) );

		// remove WC strong password script
		add_action( 'wp_print_scripts', array( $this, 'remove_wc_password_meter' ), 100 );

		// add some inline JS
		add_action( 'wp_footer', array( $this, 'enqueue_scripts' ) );

		if ( $this->is_plugin_active( 'woocommerce.php' ) ) {
			add_action( 'wp_head',   array( $this, 'bogus_gateway_styles' ) );
		}

		// add the "Bogus" testing gateway
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_bogus_gateway' ] );

		// register testing gateway for WC blocks
		add_action( 'woocommerce_blocks_loaded', [ $this, 'handle_wc_blocks_support' ] );

		// declare HPOS, WC Cart & Checkout Blocks support
		add_action( 'before_woocommerce_init', [ $this, 'handle_wc_features_compatibility' ] );

		// filter default Elavon test card
		add_filter( 'woocommerce_elavon_credit_card_default_values', array( $this, 'change_elavon_test_values' ), 10, 2 );

		// use forwarded URLs: this needs to be done as early as possible in order to set the $_SERVER['HTTPS'] var
		require_once( $this->get_plugin_path() . '/includes/Forwarded_URLs.php' );
		$this->use_forwarded_urls = new Forwarded_URLs();
	}


	/**
	 * Removes the "Please install Woo Updater" notice when an official WC extension
	 * is active but the Woo Updater plugin is not.
	 *
	 * Also removes the WC 3.3+ "Connect to WooCommerce.com" notice.
	 *
	 * @since 0.1.0
	 */
	public function muzzle_woo_updater() {
		remove_action( 'admin_notices', 'woothemes_updater_notice' );
		add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
	}


	/**
	 * Removes the strong password meter / requirement from WC 2.5+
	 * because these are dev shop passwords, not vodka drinks -- we like them weak
	 *
	 * @since 0.3.0
	 */
	public function remove_wc_password_meter() {
		wp_dequeue_script( 'wc-password-strength-meter' );
	}


	/**
	 * Add inline JavaScript.
	 *
	 * @since 0.5.0
	 */
	public function enqueue_scripts() {

		?>
		<script type="text/javascript">
				function wc_dev_get_session() {
					jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', { action: 'wc_dev_helper_get_session' }, function( response ) {
						console.log( response );
					});
				}
		</script>
		<?php
	}


	/**
	 * Add the bogus gateway to WC available gateways.
	 *
	 * @since 0.6.0
	 *
	 * @param array $gateways all available WC gateways
	 * @return array updated gateways
	 */
	public function add_bogus_gateway( $gateways ) {

		$gateways[] = '\\SkyVerge\\WooCommerce\\DevHelper\\Bogus_Gateway';
		return $gateways;
	}


	/**
	 * Adds support for WooCommerce Blocks.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function handle_wc_blocks_support() {

		if ( ! class_exists( AbstractPaymentMethodType::class ) ) {
			return;
		}

		require_once( $this->get_plugin_path() . '/includes/Bogus_Gateway_Blocks_Support.php' );
		$this->block_gateway = new Bogus_Gateway_Blocks_Support( $this->gateway );

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( $this->block_gateway );
			}
		);
	}


	/**
	 * Handles WooCommerce features compatibility.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function handle_wc_features_compatibility() {

		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		// HPOS
		FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );
		// WooCommerce Blocks
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', plugin_basename( __FILE__ ), true );
	}


	/**
	 * Changes the Elavon default payment form values.
	 *
	 * @since 0.8.0
	 *
	 * @param string[]  $defaults the gateway form defaults
	 * @param \WC_Gateway_Elavon_Converge_Credit_Card $gateway gateway instance
	 * @return string[] update default values
	 */
	public function change_elavon_test_values( $defaults, $gateway ) {

		if ( $gateway->is_test_environment() ) {

			$defaults['expiry']         = '12/' . ( date("y") + 1 );
			$defaults['account-number'] = '4124939999999990';
		}

		return $defaults;
	}


	/**
	 * Include required files
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		require_once( $this->get_plugin_path() . '/includes/Ajax.php' );
		if ( $this->is_plugin_active( 'woocommerce.php' ) && wp_doing_ajax() ) {
			$this->ajax    = new Ajax();
		}

		if ( $this->is_plugin_active( 'woocommerce.php' ) ) {
			require_once( $this->get_plugin_path() . '/includes/Bogus_Gateway.php' );
			$this->gateway = new Bogus_Gateway();
		}

		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {

			// Subscriptions helper
			require_once( $this->get_plugin_path() . '/includes/Integrations/Subscriptions.php' );
			$this->subscriptions = new Integrations\Subscriptions();
		}

		if ( $this->is_plugin_active( 'woocommerce-memberships.php' ) ) {

			// Memberships helper
			require_once( $this->get_plugin_path() . '/includes/Integrations/Memberships.php' );
			$this->memberships = new Integrations\Memberships();
		}
	}


	/**
	 * A simple way to log the actions/filers fired, simply add this query string:
	 *
	 * ?wcdh_hooks=actions|filters|all
	 *
	 * And the desired hook names will be saved to the error log along with a fired count
	 *
	 * If you want more advanced hook logging, use https://wordpress.org/plugins/debug-bar-actions-and-filters-addon/
	 *
	 * @since 0.1.0
	 */
	public function maybe_log_hooks() {

		$hooks = array();

		if ( empty( $_GET['wcdh_hooks'] ) ) {
			return;
		}

		switch( $_GET['wcdh_hooks'] ) {

			case 'actions':
				$hooks = $GLOBALS['wp_actions'];
			break;

			case 'filters':
				$hooks = $GLOBALS['wp_filter'];
			break;

			case 'all':
				$hooks = array_merge( $GLOBALS['wp_actions'], $GLOBALS['wp_filter'] );
			break;
		}

		foreach ( $hooks as $hook => $count ) {
			error_log( sprintf( '%s (%d)' . PHP_EOL, $hook, $count ) );
		}
	}


	/**
	 * Gets the plugin path.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_plugin_path() : string {

		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	/**
	 * Gets the plugin URL.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_plugin_url() : string {

		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}


	/**
	 * Helper function to determine whether a plugin is active
	 *
	 * @since 0.4.0
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return bool
	 */
	public function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( strpos( $plugin, '/' ) ) {
				// normal plugin name (plugin-dir/plugin-filename.php)
				[ , $filename ] = explode( '/', $plugin );
			} else {
				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames, true );
	}


	/**
	 * Adjust Bogus Gateway styles
	 *
	 * @since 0.6.0
	 */
	public function bogus_gateway_styles() {

		if ( is_checkout() || is_checkout_pay_page() ) {
			echo '<style type="text/css">
			#payment .payment_methods li.payment_method_bogus_gateway img {
				float: none !important;
			}
			</style>';
		}
	}


	/** Instance Getters ******************************************************/


	/**
	 * Return the Forwarded_URLs class instance
	 *
	 * @since 0.1.0
	 * @return Forwarded_URLs
	 */
	public function use_forwarded_urls() {
		return $this->use_forwarded_urls;
	}


	/**
	 * Return the Subscriptions class instance
	 *
	 * @since 0.1.0
	 * @return Integrations\Subscriptions
	 */
	public function subscriptions() {
		return $this->subscriptions;
	}


	/**
	 * Return the Memberships class instance
	 *
	 * @since 0.4.0
	 * @return Integrations\Memberships
	 */
	public function memberships() {
		return $this->memberships;
	}


	/**
	 * Return the gateway class instance
	 *
	 * @since 0.6.0
	 * @return Bogus_Gateway
	 */
	public function gateway() {
		return $this->gateway();
	}


	/** Housekeeping **********************************************************/


	/**
	 * Main WC Dev Helper Instance, ensures only one instance is/can be loaded
	 *
	 * @since 0.1.0
	 *
	 * @see wc_dev_helper()
	 * @return Plugin
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 0.1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'You cannot clone instances of WooCommerce Dev Helper.', 'woocommerce-dev-helper' ), '0.1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 0.1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'You cannot unserialize instances of WooCommerce Dev Helper.', 'woocommerce-dev-helper' ), '0.1.0' );
	}


}


/**
 * Returns the One True Instance of WC Dev Helper
 *
 * @since 0.1.0
 * @return Plugin instance
 */
function wc_dev_helper() {
	return Plugin::instance();
}

// fire it up!
wc_dev_helper();
