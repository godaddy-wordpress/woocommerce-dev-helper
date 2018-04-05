<?php
/**
 * Plugin Name: A WooCommerce Dev Helper
 * Plugin URI: https://github.com/skyverge/woocommerce-dev-helper/
 * Description: A simple plugin for helping develop/debug WooCommerce & extensions
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 0.9.0
 * Text Domain: woocommerce-dev-helper
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2018 SkyVerge [info@skyverge.com]
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WooCommerce-Dev-Helper
 * @author    SkyVerge
 * @category  Development
 * @copyright Copyright (c) 2012-2018, SkyVerge
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

class WC_Dev_Helper {


	/** @var \WC_Dev_Helper instance */
	protected static $instance;

	/** @var \WC_Dev_Helper_Ajax instance */
	private $ajax;

	/** @var \WC_Dev_Helper_Gateways instance */
	private $gateways;

	/** @var \WC_Dev_Helper_Extensions instance */
	private $extensions;

	/** @var \WC_Dev_Helper_Tools instance */
	private $tools;


	/**
	 * Bootstraps helper.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// global functions
		require_once( $this->get_plugin_path() . '/includes/functions/wc-dev-helper-functions.php' );

		// some tools need to be loaded very early
		require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-tools.php' );

		$this->tools = new WC_Dev_Helper_Tools( $this->get_plugin_path() );

		// class includes at plugins loaded time
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// remove woo updater notice
		add_action( 'admin_init', array( $this, 'muzzle_woo_updater' ) );

		// maybe log actions/filters
		add_action( 'shutdown', array( $this, 'maybe_log_hooks' ) );

		// remove WC strong password script
		add_action( 'wp_print_scripts', array( $this, 'remove_wc_password_meter' ), 100 );

		// add some inline JS
		add_action( 'wp_footer', array( $this, 'enqueue_scripts' ) );
	}


	/**
	 * Includes required files.
	 *
	 * @internal
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		if ( $this->is_woocommerce_active() ) {

			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-gateways.php' );

			$this->gateways = new WC_Dev_Helper_Gateways();

			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-extensions.php' );

            $this->extensions = new WC_Dev_Helper_Extensions();

            require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-ajax.php' );

			if ( is_ajax() ) {
				$this->ajax = new WC_Dev_Helper_Ajax();
			}
		}
	}


	/**
	 * Removes the "Please install Woo Updater" notice when an official WC extension is active but the Woo Updater plugin is not.
	 *
	 * Also removes the WC 3.3+ "Connect to WooCommerce.com" notice.
	 *
	 * @internal
	 *
	 * @since 0.1.0
	 */
	public function muzzle_woo_updater() {

		remove_action( 'admin_notices', 'woothemes_updater_notice' );

		add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
	}


	/**
	 * Removes the strong password meter / requirement from WC 2.5+.
	 * Because these are dev shop passwords, not vodka drinks -- we like them weak :)
	 *
	 * @internal
	 *
	 * @since 0.3.0
	 */
	public function remove_wc_password_meter() {

		wp_dequeue_script( 'wc-password-strength-meter' );
	}


	/**
	 * Adds inline JavaScript.
	 *
	 * @internal
	 *
	 * @see \WC_Dev_Helper_Ajax::get_session_data()
	 *
	 * @since 0.5.0
	 */
	public function enqueue_scripts() {

		?>
		<script type="text/javascript">
			function wc_dev_get_session() {
				jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', { action: 'wc_dev_helper_get_session' }, function( response ) {
					console.log( response );
				} );
			}
		</script>
		<?php
	}


	/**
	 * Logs plugin hooks on shutdown.
	 *
	 * A simple way to log the actions/filers fired, simply add this query string:
	 *
	 * ?wcdh_hooks=actions|filters|all
	 *
	 * And the desired hook names will be saved to the error log along with a fired count
	 * If you want more advanced hook logging, use https://wordpress.org/plugins/debug-bar-actions-and-filters-addon/
	 *
	 * @internal
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


	/** Helper Methods ******************************************************/


	/**
	 * Returns the plugin path.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_plugin_path() {

		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	/**
	 * Checks whether a plugin is installed and activated.
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
     * Checks whether WooCommerce is installed and activated.
     *
     * @since 0.9.0
     *
	 * @return bool
	 */
	public function is_woocommerce_active() {

	    return $this->is_plugin_active( 'woocommerce.php' );
    }


	/** Instance Getters ******************************************************/


	/**
	 * Returns the tools instance.
	 *
	 * @since 0.9.0
	 *
	 * @return \WC_Dev_Helper_Tools
	 */
	public function get_tools_instance() {

		return $this->tools;
	}


	/**
	 * Returns the AJAX handler instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Dev_Helper_Ajax
	 */
	public function get_ajax_instance() {

		return $this->ajax;
	}


	/**
	 * Returns the extensions helpers handler instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Dev_Helper_Extensions
	 */
	public function get_extensions_instance() {

		return $this->extensions;
	}


	/**
	 * Returns the extensions helpers handler instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Dev_Helper_Gateways
	 */
	public function get_gateways_instance() {

		return $this->gateways;
	}


	/** Housekeeping **********************************************************/


	/**
	 * Main WC Dev Helper Instance, ensures only one instance is/can be loaded
	 *
	 * @see \wc_dev_helper()
	 *
	 * @since 0.1.0
	 *
	 * @return \WC_Dev_Helper
	 */
	public static function instance() {

		if ( null === self::$instance ) {

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
 * Returns the One True Instance of WC Dev Helper.
 *
 * @since 0.1.0
 *
 * @return \WC_Dev_Helper instance
 */
function wc_dev_helper() {

	return WC_Dev_Helper::instance();
}

// fire it up!
wc_dev_helper();
