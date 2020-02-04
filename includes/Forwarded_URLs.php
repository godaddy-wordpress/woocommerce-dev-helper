<?php
/**
 * WooCommerce Dev Helper
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * @package   WC-Dev-Helper/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\DevHelper;

defined( 'ABSPATH' ) or exit;

/**
 * Forwarded URLs Class
 *
 * Note: You really should not use this plugin in production as it could
 * have unexpected results when filtering content URLs
 *
 * @since 0.1.0
 */
class Forwarded_URLs {


	/** @var string non-forwarded host as defined in the siteurl option */
	public $non_forwarded_host;

	/** @var array values to find and replace in URLS */
	private $find_replace =  array();


	/**
	 * Setup filters
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// bail when not forwarding
		if ( ! $this->has_forwarded_host() ) {
			return;
		}

		// save for URL replacement
		$this->non_forwarded_host = parse_url( get_option( 'siteurl' ), PHP_URL_HOST );

		// from https://github.com/50east/wp-forwarded-host-urls/
		$filters = array(
			'post_link',
			'post_type_link',
			'page_link',
			'attachment_link',
			'get_shortlink',
			'post_type_archive_link',
			'get_pagenum_link',
			'get_comments_pagenum_link',
			'term_link',
			'search_link',
			'day_link',
			'month_link',
			'year_link',
			'option_siteurl',
			'blog_option_siteurl',
			'option_home',
			'admin_url',
			'home_url',
			'includes_url',
			'plugins_url',
			'site_url',
			'site_option_siteurl',
			'network_home_url',
			'network_site_url',
			'get_the_author_url',
			'get_comment_link',
			'wp_get_attachment_image_src',
			'wp_get_attachment_thumb_url',
			'wp_get_attachment_url',
			'wp_login_url',
			'wp_logout_url',
			'wp_lostpassword_url',
			'get_stylesheet_uri',
			'get_locale_stylesheet_uri',
			'script_loader_src',
			'style_loader_src',
			'get_theme_root_uri',
			'stylesheet_uri',
			'template_directory_uri',
			'stylesheet_directory_uri',
			'the_content',
			'the_content_pre',
			'wp_calculate_image_srcset',
		);

		foreach ( $filters as $filter ) {
			add_filter( $filter, array( $this, 'replace_with_forwarded_url' ) );
		}

		// prevent redirection
		add_filter( 'redirect_canonical', '__return_false' );

		// if accessing via SSL, let WP know (see notes under https://codex.wordpress.org/Function_Reference/is_ssl)
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			$_SERVER['HTTPS'] = 'on';
		}
	}


	/**
	 * Returns true if forwarding URLs
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	private function has_forwarded_host() {

		return array_key_exists( 'HTTP_X_FORWARDED_HOST', $_SERVER ) || array_key_exists( 'HTTP_X_ORIGINAL_HOST', $_SERVER );
	}


	/**
	 * Returns the forwarded host
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_forwarded_host() {

		// are we using Forward HQ?
		$host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];

		// if not, check if we're using ngrok
		$host = isset( $_SERVER['HTTP_X_ORIGINAL_HOST'] ) ? $_SERVER['HTTP_X_ORIGINAL_HOST'] : $host;

		return $host;
	}


	/**
	 * Replace incoming content with non-forwarded URLs converted to
	 * the forwarded URL
	 *
	 * Note this does not attempt to convert protocols, instead it relies on
	 * WordPress handling protocol changes properly
	 *
	 * @since 0.1.0
	 * @param mixed $content
	 * @return mixed
	 */
	public function replace_with_forwarded_url( $content ) {

		$non_forwarded_host = $this->non_forwarded_host;
		$forwarded_host     = $this->get_forwarded_host();

		// http, https, and protocol-less URLs
		$this->find_replace = array(
			"http://{$non_forwarded_host}"  => "http://{$forwarded_host}",
			"https://{$non_forwarded_host}" => "https://{$forwarded_host}",
			"//{$non_forwarded_host}"       => "//{$forwarded_host}",
		);

		if ( is_array( $content ) ) {
			// array_walk_recursive() takes the input array by reference
			array_walk_recursive( $content, [ $this, 'replace_url' ] );
		} else {
			$content = str_replace( array_keys( $this->find_replace ), array_values( $this->find_replace ), $content );
		}

		return $content;
	}


	/**
	 * Replaces URL host within strings recursively.
	 *
	 * Required because the image srcset will use upload dir, which we don't filter
	 *  (as we filter site URL), but does so before we filter site_URL.
	 * BUT we can't filter upload dir directly, because then WP won't auto-detect protocol
	 *  for us, as we'd be replacing the URL too soon.
	 * So, we filter the srcset at the last minute.
	 *
	 * @since 1.0.0
	 *
	 * @param array $element the array to operate on
	 * @param int $index the internal pointer for array_walk_recursive
	 * @return array the updated array
	 */
	private function replace_url( &$element, $index ) {
		return str_replace( array_keys( $this->find_replace ), array_values( $this->find_replace ), $element );
	}


}
