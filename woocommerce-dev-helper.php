<?php
/**
 * Plugin Name: WooCommerce Dev Helper
 * Plugin URI: https://github.com/skyverge/woocommerce-dev-helper/
 * Description: A simple plugin for helping develop/debug WooCommerce & extensions
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 0.7.0-dev
 * Text Domain: woocommerce-dev-helper
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2017 SkyVerge [info@skyverge.com]
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WooCommerce-Dev-Helper
 * @author    SkyVerge
 * @category  Development
 * @copyright Copyright (c) 2012-2017, SkyVerge
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

class WC_Dev_Helper {


	/** @var \WC_Dev_Helper instance */
	protected static $instance;

	/** @var \WC_Dev_Helper_Ajax instance */
	protected $ajax;

	/** @var \WC_Dev_Helper_Use_Forwarded_URLs instance */
	protected $use_forwarded_urls;

	/** @var \WC_Dev_Helper_Subscriptions instance */
	protected $subscriptions;

	/** @var \WC_Dev_Helper_Memberships instance */
	protected $memberships;

	/** @var \WC_Bogus_Gateway instance */
	protected $gateway;


	/**
	 * Bootstrap class
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// global functions
		require_once( $this->get_plugin_path() . '/includes/wc-dev-helper-functions.php' );

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

		// add the testing gateway
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_bogus_gateway' ) );
	}


	/**
	 * Removes the "Please install Woo Updater" notice when an official WC extension
	 * is active but the Woo Updater plugin is not
	 *
	 * @since 0.1.0
	 */
	public function muzzle_woo_updater() {
		remove_action( 'admin_notices', 'woothemes_updater_notice' );
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
	 * @param array $gateways all available WC gateways
	 * @return array updated gateways
	 */
	function add_bogus_gateway( $gateways ) {
		$gateways[] = 'WC_Bogus_Gateway';
		return $gateways;
	}


	/**
	 * Include required files
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-ajax.php' );
		if ( $this->is_plugin_active( 'woocommerce.php' ) && is_ajax() ) {
			$this->ajax    = new WC_Dev_Helper_Ajax();
		}

		if ( $this->is_plugin_active( 'woocommerce.php' ) ) {
			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-bogus-gateway.php' );
			$this->gateway = new WC_Bogus_Gateway();
		}

		// use forwarded URLs
		require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-use-forwarded-urls.php' );
		$this->use_forwarded_urls = new WC_Dev_Helper_Use_Forwarded_URLs();

		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {

			// Subscriptions helper
			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-subscriptions.php' );
			$this->subscriptions = new WC_Dev_Helper_Subscriptions();
		}

		if ( $this->is_plugin_active( 'woocommerce-memberships.php' ) ) {

			// Memberships helper
			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-memberships.php' );
			$this->memberships = new WC_Dev_Helper_Memberships();
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
	 * Return the plugin path
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_plugin_path() {

		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	/**
	 * Helper function to determine whether a plugin is active
	 *
	 * @since 0.4.0
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
				list( , $filename ) = explode( '/', $plugin );
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
	 * Return the Use_Forwarded_URLs class instance
	 *
	 * @since 0.1.0
	 * @return \WC_Dev_Helper_Use_Forwarded_URLs
	 */
	public function use_forwarded_urls() {
		return $this->use_forwarded_urls;
	}


	/**
	 * Return the Subscriptions class instance
	 *
	 * @since 0.1.0
	 * @return \WC_Dev_Helper_Subscriptions
	 */
	public function subscriptions() {
		return $this->subscriptions;
	}


	/**
	 * Return the Memberships class instance
	 *
	 * @since 0.4.0
	 * @return \WC_Dev_Helper_Memberships
	 */
	public function memberships() {
		return $this->memberships;
	}


	/**
	 * Return the gateway class instance
	 *
	 * @since 0.6.0
	 * @return \WC_Bogus_Gateway
	 */
	public function gateway() {
		return $this->gateway();
	}


	/** Housekeeping **********************************************************/


	/**
	 * Main WC Dev Helper Instance, ensures only one instance is/can be loaded
	 *
	 * @since 0.1.0
	 * @see wc_dev_helper()
	 * @return \WC_Dev_Helper
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
 * @return \WC_Dev_Helper instance
 */
function wc_dev_helper() {
	return WC_Dev_Helper::instance();
}

// fire it up!
wc_dev_helper();
