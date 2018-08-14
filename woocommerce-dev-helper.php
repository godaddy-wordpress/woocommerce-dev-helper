<?php
/**
 * Plugin Name: A WooCommerce Dev Helper
 * Plugin URI: https://github.com/skyverge/woocommerce-dev-helper/
 * Description: A simple plugin for helping develop/debug WooCommerce & extensions
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.0.0
 * Text Domain: woocommerce-dev-helper
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2018 SkyVerge [info@skyverge.com]
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2018, SkyVerge
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Dev Helper loader.
 *
 * Loads the helper plugin within the SkyVerge Plugin Framework.
 *
 * Even though it's called "WooCommerce" Dev Helper this plugin can offer some of its features in general WordPress or eCommerce development.
 * It is therefore necessary to add some additional conditional checks to see if WooCommerce is loaded before calling WooCommerce-specific methods and functions.
 *
 * @since 1.0.0
 */
class WC_Dev_Helper_Loader {


	/** minimum PHP version required by this plugin */
	const MINIMUM_PHP_VERSION = '5.3.0';

	/** minimum WordPress version required by this plugin */
	const MINIMUM_WP_VERSION = '4.4';

	/** minimum WooCommerce version required by this plugin */
	const MINIMUM_WC_VERSION = '2.6.14';

	/** the plugin name, for displaying notices */
	const PLUGIN_NAME = 'WooCommerce Dev Helper';

	/** plugin namespace */
	const PLUGIN_NAMESPACE = 'SkyVerge\WooCommerce\Dev_Helper';


	/** @var \WC_Dev_Helper_Loader single instance of this plugin */
	protected static $instance;

	/** @var array the admin notices to add */
	private $notices = array();



	/**
	 * Initializes the loader.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// if the environment checks pass, initialize the plugin
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {

		if ( ! $this->is_wp_compatible() ) {
			return;
		}

		// load the framework
		$this->load_framework();

		// autoload plugin and vendor files
		$loader = require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

		// register plugin namespace with autoloader
		$loader->addPsr4( self::PLUGIN_NAMESPACE . '\\', __DIR__ . '/src' );

		// include the mail plugin class file
		require_once( plugin_dir_path( __FILE__ ) . 'src/Plugin.php' );

		require_once( plugin_dir_path( __FILE__ ) . 'src/Functions.php' );

		wc_dev_helper();
	}


	/**
	 * Loads the base framework classes.
	 *
	 * @since 1.0.0
	 */
	protected function load_framework() {

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_2_0\\SV_WC_Plugin' ) ) {

			require_once( plugin_dir_path( __FILE__ ) . 'vendor/skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php' );
		}
	}


	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {

		if ( ! $this->is_environment_compatible() ) {

			$this->deactivate_plugin();

			wp_die( self::PLUGIN_NAME . ' could not be activated. ' . $this->get_environment_message() );
		}
	}


	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {

		if ( ! $this->is_environment_compatible() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {

			$this->deactivate_plugin();

			$this->add_admin_notice( 'bad_environment', 'error', self::PLUGIN_NAME . ' has been deactivated. ' . $this->get_environment_message() );
		}
	}


	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_notices() {

		if ( ! $this->is_wp_compatible() ) {

			$this->add_admin_notice( 'update_wordpress', 'error', sprintf(
				'%s requires WordPress version %s or higher. Please %supdate WordPress &raquo;%s',
				'<strong>' . self::PLUGIN_NAME . '</strong>',
				self::MINIMUM_WP_VERSION,
				'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">', '</a>'
			) );
		}
	}


	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_wp_compatible() {

		return version_compare( get_bloginfo( 'version' ), self::MINIMUM_WP_VERSION, '>=' );
	}


	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_environment_compatible() {

		return version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=' );
	}


	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_environment_message() {

		return sprintf( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', self::MINIMUM_PHP_VERSION, PHP_VERSION );
	}


	/**
	 * Deactivates the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function deactivate_plugin() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}


	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug the notice slug
	 * @param string $class the notice class
	 * @param string $message the notice message
	 */
	public function add_admin_notice( $slug, $class, $message ) {

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}


	/**
	 * Displays admin notices.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {

		foreach ( $this->notices as $notice_key => $notice ) :

			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p><?php echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) ); ?></p>
			</div>
			<?php

		endforeach;
	}


	/**
	 * Returns the loader instance.
	 *
	 * Ensures only one instance is loaded at one time.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Dev_Helper_Loader
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}


// reintroduces a WooCommerce core function to ensure compatibility if WooCommerce isn't active
if ( ! function_exists( 'is_ajax' )  ) {
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}

// fire it up!
WC_Dev_Helper_Loader::instance();
