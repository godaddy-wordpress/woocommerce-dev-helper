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

defined( 'ABSPATH' ) or exit;

/**
 * Importer & Exporter of wp_options handler.
 *
 * Inspired by https://wordpress.org/plugins/options-importer/
 *
 * @since 0.9.0
 */
class WC_Dev_Helper_Import_Export_Options {


	/** @var int imported file identifier */
	private $import_file_id = 0;

	/** @var array imported data */
	private $import_data = array();

	/** @var string holds a file handling error message */
	private $import_error = '';

	/** @var string import transient key name (where %d is the current import file job ID) */
	private $import_transient_key = 'wc_dev_helper_options_import_%d';


	/**
	 * Import / Export options handler constructor.
	 *
	 * @since 0.9.0
	 */
	public function __construct() {

		// importer
		add_action( 'admin_init', array( $this, 'add_importer' ) );

		// exporter
		add_action( 'init', array( $this, 'add_exporter' ) );
	}


	/**
	 * Returns a list of blacklisted options to ignore.
	 *
	 * @since 0.9.0
	 *
	 * @param string $which one of 'export' or 'import'
	 * @return string[]
	 */
	private function get_excluded_options( $which ) {

		$blacklist = array();

		if ( 'import' === $which ) {

			/**
			 * Filters options to be blacklisted and excluded from import.
			 *
			 * @since 0.9.0
			 *
			 * @param string[] $blacklist array of option names
			 */
			$blacklist = apply_filters( 'wc_dev_helper_exclude_import_options', array(
				'active_plugins',
				'current_theme',
				'current_theme_supports_woocommerce',
				'db_version',
				'default_product_cat',
				'home',
				'new_admin_email',
				'product_cat_children',
				'siteurl',
				'stickyposts',
				'stylesheet',
				'template',
			) );

		} elseif ( 'export' === $which ) {

			/**
			 * Filters options to be excluded from an export.
			 *
			 * @since 0.9.0
			 *
			 * @param string[] $blacklist array of option names
			 */
			$blacklist = apply_filters( 'wc_dev_helper_exclude_export_options', array() );
		}

		return $blacklist;
	}


	/**
	 * Registers a WordPress importer.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function add_importer() {

		register_importer(
			'wc-dev-helper-wordpress-options-import',
			__( 'WordPress Options', 'woocommerce-dev-helper' ),
			__( 'Import WordPress options from a JSON file.', 'woocommerce-dev-helper' ),
			array( $this, 'handle_importer_steps' )
        );
	}


	/**
	 * Adds and handlers export options for the WordPress exporter.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function add_exporter() {

		add_action( 'export_filters', array( $this, 'add_export_filters' ) );
		add_filter( 'export_args',    array( $this, 'handle_export_args' ) );
		add_action( 'export_wp',      array( $this, 'export_options' ) );
	}


	/**
	 * Adds a radio option to WordPress export options.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function add_export_filters() {

		?>
		<p>
			<label>
				<input
					type="radio"
					name="content"
					value="options"
				/> <?php esc_html_e( 'Options', 'woocommerce-dev-helper' ); ?>
			</label>
		</p>
		<?php
	}


	/**
	 * Flags an export job to export options exclusively.
	 *
	 * @see \WC_Dev_Helper_Import_Export_Options::export_options()
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 *
	 * @param array $export_args associative array of export arguments being filtered
	 * @return array export arguments
	 */
	public function handle_export_args( $export_args ) {

		if ( isset( $_GET['content'] ) && 'options' === $_GET['content'] ) {
			$export_args = array( 'options' => true );
		}

		return $export_args;
	}


	/**
	 * Export options to JSON.
	 *
	 * @since 0.9.0
	 *
	 * @param array $export_args associative array
	 */
	public function export_options( $export_args ) {
		global $wpdb;

		if ( isset( $export_args['options'] ) && true === $export_args['options'] ) {

			$site_name = trim( sanitize_key( get_bloginfo( 'name' ) ) );
			$file_name = sanitize_file_name( ( $site_name ? $site_name . '_' : '' ) . 'wp_options_' . date( 'Y_m_d' ) ) . '.json';

			// set headers
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $file_name );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );

			// grab options (exclude multisite)
			$multisite_prefix  = $wpdb->prefix . '%d_%';
			$exclude_multisite = is_multisite() ? $wpdb->prepare( "AND `option_name` NOT LIKE '{$multisite_prefix}'", get_current_blog_id() ) : '';
			$found_options     = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE `option_name` NOT LIKE '%_transient_%' {$exclude_multisite}" );
			$export_options    = array();

			if ( ! empty( $found_options ) ) {

				// get options to exclude from exporting
				$excluded_options = $this->get_excluded_options( 'export' );

				foreach ( $found_options as $item ) {

					if ( isset( $item->option_name, $item->option_value ) ) {

						// skip blacklisted
						if ( in_array( $item->option_name, $excluded_options, true ) ) {
							continue;
						}

						$export_options[ $item->option_name ] = array(
							'value'    => $item->option_value,
							'autoload' => isset( $item->autoload ) && 'yes' === $item->autoload,
						);
					}
				}

				ksort( $export_options );

				echo wp_json_encode( $export_options );
			}

			exit;
		}
	}


	/**
	 * Returns the import transient key.
	 *
	 * @since 0.9.0
	 *
	 * @return string
	 */
	private function get_import_transient_key() {

		return null !== $this->import_transient_key && null !== $this->import_file_id ? sprintf( $this->import_transient_key, $this->import_file_id ) : '';
	}


	/**
	 * Adds an importer page and handles import stages.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function handle_importer_steps() {

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Import WordPress Options', 'woocommerce-dev-helper' ); ?></h2>
			<?php

			$step      = ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 0;
			$import_id = ! empty( $_POST['import_id'] ) ? absint( $_POST['import_id'] ) : 0;

			switch ( $step ) :

				case 0:

					?>
					<div class="narrow">
						<p><?php esc_html_e( 'Upload a JSON file with WordPress options to import. You will have a chance to review the data to import and confirm before the import process starts.', 'woocommerce-dev-helper' ); ?></p>
						<?php wp_import_upload_form( 'admin.php?import=wc-dev-helper-wordpress-options-import&amp;step=1' ); ?>
					</div>
					<?php

				break;

				case 1:

					check_admin_referer( 'import-upload' );

					// output import preview
					if ( $this->handle_file_upload() ) :
						$this->handle_import_options();
					// output an error message
					elseif ( is_string( $this->import_error ) && '' !== $this->import_error ) :
						$this->handle_import_errors();
					endif;

				break;

				case 2:

					check_admin_referer( 'wc-dev-helper-import-wordpress-options' );

					$this->import_file_id = $import_id;

					if ( false !== ( $this->import_data = get_transient( $this->get_import_transient_key() ) ) ) {

						$this->import_options();
					}

				break;

			endswitch;

			?>
		</div>
		<?php
	}


	/**
	 * Handles the JSON upload.
	 *
	 * @since 0.9.0
	 *
	 * @return bool success
	 */
	private function handle_file_upload() {

		$file    = wp_import_handle_upload();
		$success = false;

		if ( isset( $file['error'] ) ) {
			$this->import_error = esc_html( $file['error'] );
		} elseif ( ! isset( $file['file'], $file['id'] ) ) {
			$this->import_error = __( 'The file did not upload properly. Please try again.', 'woocommerce-dev-helper' );
		}

		$this->import_file_id = absint( $file['id'] );

		if ( ! file_exists( $file['file'] ) ) {
			wp_import_cleanup( $this->import_file_id );
			$this->import_error = sprintf( __( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'woocommerce-dev-helper' ), '<code>' . esc_html( $file['file'] ) . '</code>' );
		}

		if ( ! is_file( $file['file'] ) ) {
			wp_import_cleanup( $this->import_file_id );
			$this->import_error = __( 'Invalid file, please try again.', 'woocommerce-dev-helper' );
		}

		$file_contents = file_get_contents( $file['file'] );

		if ( empty( $file_contents ) ) {
			wp_import_cleanup( $this->import_file_id );
			$this->import_error = __( 'Import file empty or invalid.', 'woocommerce-dev-helper' );
		}

		if ( empty( $this->import_error ) ) {

			$this->import_data = json_decode( $file_contents, true );

			set_transient( $this->get_import_transient_key(), $this->import_data, DAY_IN_SECONDS );

			wp_import_cleanup( $this->import_file_id );

			$success = true;
		}

		return $success;
	}


	/**
	 * Outputs an HTML error message.
	 *
	 * @since 0.9.0
	 */
	private function handle_import_errors() {

		?>
		<div class="error" style="margin: 20px 0 30px;">
			<p><?php printf( __( '%1$sError:%2$s %3$s', 'woocommerce-dev-helper' ), '<strong>', '</strong>', $this->import_error ); ?></p>
		</div>
		<div class="narrow">
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?import=wc-dev-helper-wordpress-options-import' ) ); ?>"><?php esc_html_e( 'Return to File Upload', 'woocommerce-dev-helper' ); ?></a></p>
		</div>
		<?php
	}


	/**
	 * Handles pre-import settings when importing options.
	 *
	 * @since 0.9.0
	 */
	private function handle_import_options() {

		?>
		<style type="text/css">

			#importing_options {
				border-collapse: collapse;
			}

			#importing_options th {
				text-align: left;
			}

			#importing_options td,
			#importing_options th {
				padding: 5px 10px;
				border-bottom: 1px solid #dfdfdf;
			}

			#importing_options pre {
				white-space: pre-wrap;
				max-height: 100px;
				overflow-y: auto;
				background: #fff;
				padding: 5px;
			}

			div.error#import_all_warning {
				margin: 25px 0 5px;
			}

		</style>

		<script type="text/javascript">

			jQuery( function( $ ) {

				$( '.which-options' ).on( 'change', function() {

					if ( 'all' === $( this ).val() ) {
						$( '#option_importer_details' ).hide();
					} else {
						$( '#option_importer_details' ).show();
					}
				} );

				$( '.options-bulk-select' ).on( 'click', function( e ) {
					e.preventDefault();

					if ( 'all' === $( this ).data( 'select' ) ) {
						$( '#importing_options input:checkbox' ).prop( 'checked', true )
					} else {
						$( '#importing_options input:checkbox' ).prop( 'checked', false )
					}
				} );

				$( '#overwrite_current' ).on( 'click', function() {

					if ( $( this ).is( ':checked' ) ) {
						$( '#import_all_warning' ).show();
					} else {
						$( '#import_all_warning' ).hide();
					}
				} );

			} );

		</script>

		<form
			action="<?php echo admin_url( 'admin.php?import=wc-dev-helper-wordpress-options-import&amp;step=2' ); ?>"
			method="post">

			<?php wp_nonce_field( 'wc-dev-helper-import-wordpress-options' ); ?>

			<input
				type="hidden"
				name="import_id"
				value="<?php echo absint( $this->import_file_id ); ?>"
			/>

			<h3><?php esc_html_e( 'Which options would you like to import?', 'woocommerce-dev-helper' ) ?></h3>
			<p>
				<label>
					<input
						type="radio"
						class="which-options"
						name="settings[which_options]"
						value="all"
						checked="checked"
					/> <?php esc_html_e( 'All Options', 'woocommerce-dev-helper' ); ?>
				</label>
				<br />
				<label>
					<input
						type="radio"
						class="which-options"
						name="settings[which_options]"
						value="specific"
					/> <?php esc_html_e( 'Specific Options', 'woocommerce-dev-helper' ); ?>
				</label>
			</p>

			<div id="option_importer_details" style="display: none;">

				<h3><?php esc_html_e( 'Select the options to import', 'woocommerce-dev-helper' ); ?></h3>
				<p>
					<a href="#" class="button btn-small options-bulk-select" data-select="all"><?php esc_html_e( 'Select All', 'woocommerce-dev-helper' ); ?></a>
					&nbsp;&nbsp;
					<a href="#" class="button btn-small options-bulk-select" data-select="none"><?php esc_html_e( 'Select None', 'woocommerce-dev-helper' ); ?></a>
				</p>

				<table id="importing_options">

					<thead>
						<tr>
							<th><?php esc_html_e( 'Import',      'woocommerce-dev-helper' ); ?></th>
							<th><?php esc_html_e( 'Option Name', 'woocommerce-dev-helper' ); ?></th>
							<th><?php esc_html_e( 'New Value',   'woocommerce-dev-helper' ); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php $excluded_options = $this->get_excluded_options( 'import' ); ?>
						<?php foreach ( (array) $this->import_data as $option_name => $option_data ) : ?>

							<?php if ( isset( $option_data['value'] ) && ! in_array( $option_name, $excluded_options, true ) ) : ?>

								<?php $option_value = $option_data['value']; ?>
								<tr>
									<td>
										<label>
											<input
												type="checkbox"
												name="options[]"
												value="<?php echo esc_attr( $option_name ) ?>"
												checked="checked"
											/>
										</label>
									</td>
									<td><?php echo '<code>' . esc_html( $option_name ) . '</code>' ?></td>
									<?php if ( null === $option_value ) : ?>
										<td><em>null</em></td>
									<?php elseif ( '' === $option_value ) : ?>
										<td><em>empty string</em></td>
									<?php elseif ( false === $option_value ) : ?>
										<td><em>false</em></td>
									<?php elseif ( is_string( $option_value ) || is_numeric( $option_value ) ) : ?>
										<td><pre><?php echo strlen( $option_value ) >= 80 ? substr( esc_html( $option_value ), 0, 80 ) . '...' : esc_html( $option_value ); ?></pre></td>
									<?php else : ?>
										<td><em>undetermined</em></td>
									<?php endif ?>
								</tr>

							<?php endif; ?>

						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<h3><?php esc_html_e( 'Additional Settings', 'woocommerce-dev-helper' ); ?></h3>

			<p>
				<input
					type="checkbox"
					value="1"
					name="settings[replace]"
					id="replace_url"
				/>
				<label for="replace_url"><?php esc_html_e( 'Replace URLs', 'woocommerce-dev-helper' ); ?></label>
			</p>
			<p class="description"><?php esc_html_e( 'When enabled, if a site URL option is found in the import set, it will try to replace matching URLs found in all import option values with this site URL.', 'woocommerce-dev-helper' ); ?></p>

			<p>
				<input
					type="checkbox"
					value="1"
					name="settings[overwrite]"
					id="overwrite_current"
				/>
				<label for="overwrite_current"><?php esc_html_e( 'Overwrite existing options', 'woocommerce-dev-helper' ); ?></label>
			</p>
			<p class="description"><?php esc_html_e( 'If you keep this disabled, options will be skipped if they currently exist in your database.', 'woocommerce-dev-helper' ); ?></p>

			<div class="error inline" id="import_all_warning" style="display: none;">
				<p class="description"><?php esc_html_e( 'Caution! Importing all options with the overwrite option enabled could break this site. Only proceed if you know exactly what you are doing.', 'woocommerce-dev-helper' ); ?></p>
			</div>

			<div>
				<p class="submit">
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?import=wc-dev-helper-wordpress-options-import' ) ); ?>"><?php esc_html_e( 'Return to File Upload', 'woocommerce-dev-helper' ); ?></a>
					&nbsp;&nbsp;
					<input
						type="submit"
						name="submit"
						id="submit"
						class="button button-primary"
						value="<?php esc_html_e( 'Import Options', 'woocommerce-dev-helper' ); ?>"
					/>
				</p>
			</div>

		</form>
		<?php

		$import_data = get_transient( $this->get_import_transient_key() );

		// cleanup
		$this->import_data  = ! empty( $import_data ) ? (array) $import_data : array();
		$this->import_error = '';
	}


	/**
	 * Runs an import job.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function import_options() {

		$options_to_import = array();

		$imported = $processed = $skipped = 0;

		if ( empty( $_POST['settings']['which_options'] ) ) {
			$this->import_error = esc_html( 'An error occurred during the import form submission. Please try again.', 'woocommerce-dev-helper' );
		}

		if ( empty( $this->import_data ) || ! is_array( $this->import_data ) ) {
			$this->import_error = esc_html( 'The data to import appears empty or invalid.', 'woocommerce-dev-helper' );
		}

		if ( 'all' === $_POST['settings']['which_options'] ) {

			$options_to_import = array_keys( $this->import_data );

		} elseif ( 'specific' === $_POST['settings']['which_options'] ) {

			if ( empty( $_POST['options'] ) ) {
				$this->import_error = esc_html__( 'There do not appear to be any options to import. Did you select any?', 'woocommerce-dev-helper' );
			} else {
				$options_to_import = (array) $_POST['options'];
			}
		}

		if ( empty( $this->import_error ) ) {

			$import_data       = (array) $this->import_data;
			$overwrite_options = ( ! empty( $_POST['settings']['overwrite'] ) && '1' === $_POST['settings']['overwrite'] );
			$replace_urls      = ( ! empty( $_POST['settings']['replace'] ) && '1' === $_POST['settings']['replace'] );
			$option_not_set    = uniqid( 'wc_dev_helper_unset_option', false );
			$exclude_options   = $this->get_excluded_options( 'import' );

			if ( $replace_urls && isset( $import_data['siteurl']['value'] ) ) {
				$import_site_url  = trim( $import_data['siteurl']['value'] );
				$this_site_url    = trim( get_bloginfo( 'url' ) );
			} else {
				$import_site_url  = '';
				$this_site_url    = '';
				$replace_urls     = false;
			}

			foreach ( $options_to_import as $option_name ) {

				$processed++;

				if ( isset( $import_data[ $option_name ]['value'] ) ) {

					if ( in_array( $option_name, $exclude_options, true ) ) {

						echo "\n<p>" . sprintf( __( 'Skipped excluded option %s.', 'woocommerce-dev-helper' ), '<code>' . esc_html( $option_name ) . '</code>' ) . '</p>';
						continue;
					}

					if ( ! $overwrite_options ) {

						// we're going to use a random hash as our default, to know if something is set or not
						$old_value = get_option( $option_name, $option_not_set );

						// only import the setting if it's not present
						if ( $old_value !== $option_not_set ) {

							echo "\n<p>" . sprintf( __( 'Skipped option %s because it currently exists and overwriting is disabled.', 'woocommerce-dev-helper' ), '<code>' . esc_html( $option_name ) . '</code>' ) . '</p>';
							continue;
						}
					}

					$raw_value = $import_data[ $option_name ]['value'];

					if ( $replace_urls && is_string( $raw_value ) ) {
						$raw_value = str_replace( $import_site_url, $this_site_url, $raw_value );
					}

					$option_value = maybe_unserialize( $raw_value );

					if ( empty( $import_data[ $option_name ]['autoload'] ) ) {
						delete_option( $option_name );
						add_option( $option_name, $option_value, '', 'no' );
					} else {
						update_option( $option_name, $option_value );
					}

					$imported++;

				} elseif ( 'specific' === $_POST['settings']['which_options'] ) {

					echo "\n<p>" . sprintf( __( 'Failed to import option %s; it does not seem to be in the import file.', 'woocommerce-dev-helper' ), '<code>' . esc_html( $option_name ) . '</code>' ) . '</p>';

				} else {

					echo "\n<p>" . sprintf( __( 'Failed to import option %s.', 'woocommerce-dev-helper' ), '<code>' . esc_html( $option_name ) . '</code>' ) . '</p>';
				}
			}

			$skipped = max( 0, $processed - $imported );

			if ( $processed > 0 ) {

				if ( $imported < $processed ) {
					echo "\n<p><strong>" . sprintf( _n( '%d option processed.', '%d options processed.', $processed, 'woocommerce-dev-helper' ), $processed ) . '</strong></p>';
				}

				if ( $skipped > 0 ) {
					echo "\n<p><strong>" . sprintf( _n( '%d option skipped.', '%d options skipped.', $skipped, 'woocommerce-dev-helper' ), $skipped ) . '</strong></p>';
				}

				if ( $imported > 0 ) {
					echo "\n<p><strong>" . sprintf( _n( '%d option imported.', '%d options imported.', $imported, 'woocommerce-dev-helper' ), $imported ) . '</strong></p>';
				} else {
					echo "\n<p><strong>" . esc_html__( 'No options were successfully imported.', 'woocommerce-dev-helper' ) . '</strong></p>';
				}

			} else {

				echo "\n<p><strong>" . esc_html__( 'No options were successfully imported.', 'woocommerce-dev-helper' ) . '</strong></p>';
			}

		} else {

			$this->handle_import_errors();
		}

		// clean up
		delete_transient( $this->get_import_transient_key() );

		$this->import_file_id = 0;
		$this->import_data    = array();
		$this->import_error   = '';
	}


}
