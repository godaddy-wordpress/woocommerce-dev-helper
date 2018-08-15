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
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_2_0 as Framework;

/**
 * Memberships Bulk Generator.
 *
 * Tool to generate membership objects in bulk.
 *
 * When launched, a background process will create the following items in the following order:
 *
 * - users: to assign memberships to
 * - blog posts: to assign to membership plans access rules
 * - post categories: to assign to membership plans access rules
 * - products: to assign to membership plans access, purchase, discount rules, or as the product that grants access
 * - subscription products: same as above, only if Subscriptions is active
 * - product categories: to assign to membership plans for access, purchase, discount rules
 * - membership plans: plans that contain rules that target content and products
 * - user memberships: assigned to created users for a variety of plans, randomly
 *
 * The handler also adds a settings page within the Memberships admin screens to offer an UI.
 * Admins can tweak the number of memberships to generate and other options.
 *
 * @since 1.0.0
 */
class Bulk_Generator extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Background job constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->prefix   = 'wc_dev_helper';
		$this->action   = 'memberships_bulk_generation';
		$this->data_key = 'users';

		parent::__construct();
	}


	/**
	 * Processes an item and creates a user membership assigned to a user.
	 *
	 * This may trigger a cascade of objects that need to be created.
	 * For example, to create a user membership, an user must exist first.
	 * A plan to be assigned must exist as well. A plan contains references to other WordPress/WooCommerce objects and so on...
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_login item to process
	 * @param \stdClass $job job object
	 * @return \stdClass modified job object
	 */
	public function process_item( $user_login, $job ) {

		return $this->create_user_membership( $this->get_user( $user_login ), $job );
	}


	/**
	 * Creates a user membership for a user.
	 *
	 * Membership details and plan assignment are randomly chosen.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_User $user user object
	 * @param \stdClass $job job object
	 * @return \stdClass $job job object
	 */
	private function create_user_membership( $user, $job ) {

		if ( $user instanceof \WP_User ) {

			// each user gets randomly assigned from 1 to 3 plans
			$plan_slugs = array_rand( $this->get_membership_plans_slugs(), mt_rand( 1, 3 ) );

			if ( ! empty( $plan_slugs ) )  {

				foreach ( $plan_slugs as $plan_slug ) {

					// unless previously generated, this may also trigger a plan creation, with a cascade of objects attached to it
					if ( $membership_plan = $this->get_membership_plan( $plan_slug ) ) {

						// stores a record of new objects created during plan creation
						$job = $this->record_created_objects( $membership_plan, $job );

						try {

							// finally, creates a membership
							wc_memberships_create_user_membership( array(
								'plan_id'    => $membership_plan->get_id(),
								'user_id'    => $user->ID,
								'product_id' => 0,
								'order_id'   => 0,
							) );

						} catch ( \SV_WC_Plugin_Exception $e ) {

							wc_dev_helper()->log( $e->getMessage() );
						}
					}
				}
			}
		}

		return $job;
	}


	/**
	 * Returns a customer user, or creates one if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param $which_user
	 * @return bool|null|\WP_User
	 */
	private function get_user( $which_user ) {

		$login = $this->add_prefix( $which_user );
		$user  = get_user_by( 'login', $login );

		if ( ! $user ) {
			$user = $this->create_user( $which_user );
		}

		return $user;
	}


	/**
	 * Creates a user customer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_login user login slug
	 * @return null|\WP_User
	 */
	private function create_user( $user_login ) {

		$user    = null;
		$login   = $this->add_prefix( $user_login );
		$user_id = wp_insert_user( array(
			'user_login' => $login,
			'user_pass'  => wp_generate_password(),
			'user_email' => str_replace( '-', '_', "{$login}@example.com" ),
			'role'       => 'customer',
		) );

		if ( is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
		}

		return $user;
	}


	/**
	 * Returns slugs of all users to be generated in a process.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit maximum number of users generated
	 * @return string[] array of user slugs
	 */
	public function get_users_slugs( $limit ) {

		$users = array();

		for ( $i = 0; $i < $limit; $i++ ) {
			$users[] = $this->add_prefix( $i );
		}

		return $users;
	}


	/**
	 * Returns a membership plan.
	 *
	 * May create the plan if not found, then return it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_plan membership plan slug
	 * @return null|\WC_Memberships_Integration_Subscriptions_Membership_Plan|\WC_Memberships_Membership_Plan
	 */
	private function get_membership_plan( $which_plan ) {

		$plan      = null;
		$plan_data = $this->get_membership_plan_data( $which_plan );

		if ( ! empty( $plan_data ) ) {

			$plan = wc_memberships_get_membership_plan( $this->add_prefix( $which_plan ) );

			// check if plan exists, or create it
			if ( ! $plan ) {
				$plan = $this->create_membership_plan( $which_plan );
			}
		}

		return $plan instanceof \WC_Memberships_Membership_Plan ? $plan : null;
	}


	/**
	 * Creates a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_plan plan slug
	 * @return null|\WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_Membership_Plan
	 */
	private function create_membership_plan( $which_plan ) {

		$plan      = null;
		$plan_slug = $this->add_prefix( $which_plan );
		$plan_data = $this->get_membership_plan_data( $which_plan );

		if ( is_array( $plan_data ) && isset( $plan_data['post_title'] ) ) {

			$post_id = wp_insert_post( array(
				'post_author' => get_current_user_id(),
				'post_type'   => 'wc_membership_plan',
				'post_status' => 'publish',
				'post_name'   => sanitize_text_field( $plan_slug ),
				'post_title'  => sanitize_text_field( $plan_data['post_title'] ),
			) );

			if ( is_numeric( $post_id ) ) {

				$plan   = new \WC_Memberships_Membership_Plan( $post_id );
				$access = isset( $plan_data['access_method'] ) ? $plan_data['access_method'] : 'manual-only';

				// set and validate access method
				$plan->set_access_method( $access );

				// set access products
				if ( 'purchase' === $plan->get_access_method() ) {

					// one product is picked at random to be the access product
					$random_product = array_rand( array_keys( $this->get_products_data() ), 1 );
					$access_product = $this->get_product( $random_product );

					// sanity check: revert to manual-only in case of errors
					if ( ! $access_product instanceof \WC_Product ) {
						$plan->set_access_method( 'manual-only' );
					} else {
						$plan->set_product_ids( $access_product->get_id() );
					}
				}

				// set plan rules
				$this->create_membership_plan_rules( $plan, $plan->get_rules() );
			}
		}

		return $plan;
	}


	/**
	 * Returns plan data given a membership plan slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_plan membership plan slug
	 * @return null|array plan data if data exists
	 */
	public function get_membership_plan_data( $which_plan ) {

		$plan = $this->remove_prefix( $which_plan );
		$data = $this->get_membership_plans_data();

		return ! empty( $data[ $plan ] ) ? $data[ $plan ] : array();
	}


	/**
	 * Returns data for plans to be created in bulk.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array of plans data with slugs shorthands for keys
	 */
	private function get_membership_plans_data() {

		return array(
			// a manually assigned plan
			'test-membership-plan-a' => array(
				'post_title'    => __( 'Test Membership Plan A (manual only)', 'woocommerce-dev-helper' ),
				'access_method' => 'manual-only',
			),
			// a signup-access membership plan
			'test-membership-plan-b' => array(
				'post_title'    => __( 'Test Membership Plan B (signup)', 'woocommerce-dev-helper' ),
				'access_method' => 'signup',
			),
			// a plan with products (simple, variable) to purchase to access to
			'test-membership-plan-c' => array(
				'post_title'    => __( 'Test Membership Plan C (purchase)', 'woocommerce-dev-helper' ),
				'access_method' => 'purchase',
			),
		);
	}


	/**
	 * Returns an array of plan slugs created by the bulk generator.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] array of plan slugs
	 */
	public function get_membership_plans_slugs() {

		$plans = array();
		$slugs = array_keys( $this->get_membership_plans_data() );

		foreach ( $slugs as $slug ) {
			$plans[] = $this->add_prefix( $slug );
		}

		return $plans;
	}


	/**
	 * Creates rules for a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_User_Membership $plan plan object
	 * @param \WC_Memberships_Membership_Plan_Rule[] array of rules (default empty array as there are no rules yet)
	 */
	private function create_membership_plan_rules( $plan, array $rules ) {

		$rules = $this->create_membership_plan_content_access_rules( $plan, $rules );
		$rules = $this->create_membership_plan_products_access_rules( $plan, $rules );
		$rules = $this->create_membership_plan_products_purchase_rules( $plan, $rules );
		$rules = $this->create_membership_plan_products_discount_rules( $plan, $rules );

		$plan->set_rules( $rules );
	}


	/**
	 * Creates content access rules for a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_User_Membership $plan plan object
	 * @param \WC_Memberships_Membership_Plan_Rule[] array of rules (default empty array as there are no rules yet)
	 * @return \WC_Memberships_Membership_Plan_Rule[]
	 */
	private function create_membership_plan_products_access_rules( $plan, array $rules ) {

		// TODO
		return $rules;
	}


	/**
	 * Creates product purchase rules for a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_User_Membership $plan plan object
	 * @param \WC_Memberships_Membership_Plan_Rule[] array of rules (default empty array as there are no rules yet)
	 * @return \WC_Memberships_Membership_Plan_Rule[]
	 */
	private function create_membership_plan_products_purchase_rules( $plan, array $rules ) {

		// TODO
		return $rules;
	}


	/**
	 * Creates product discount rules for a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_User_Membership $plan plan object
	 * @param \WC_Memberships_Membership_Plan_Rule[] array of rules (default empty array as there are no rules yet)
	 * @return \WC_Memberships_Membership_Plan_Rule[]
	 */
	private function create_membership_plan_products_discount_rules( $plan, array $rules ) {

		// TODO
		return $rules;
	}


	/**
	 * Creates product access rules for a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_User_Membership $plan plan object
	 * @param \WC_Memberships_Membership_Plan_Rule[] array of rules (default empty array as there are no rules yet)
	 * @return \WC_Memberships_Membership_Plan_Rule[]
	 */
	private function create_membership_plan_content_access_rules( $plan, array $rules ) {

		// TODO
		return $rules;
	}


	/**
	 * Returns a product for use by the generator, creates one if doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_product the product slug
	 * @return null|\WC_Product
	 */
	private function get_product( $which_product ) {

		$product = null;

		if ( $product_data = $this->get_product_data( $which_product ) ) {

			$product_posts = get_posts( array(
				'name'           => $this->add_prefix( $which_product ),
				'post_type'      => 'product',
				'posts_per_page' => 1,
			) );

			if ( is_array( $product_posts ) && isset( $product_posts[0] ) ) {
				$product = wc_get_product( $product_posts[0] );
			} else {
				$product = $this->create_product( $which_product );
			}
		}

		return $product instanceof \WC_Product ? $product : null;
	}


	/**
	 * Creates a product for use by the generator.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_plan plan data to retrieve
	 * @return null|\WC_Product
	 */
	private function create_product( $which_plan ) {

		$product      = null;
		$product_slug = $this->add_prefix( $which_plan );
		$product_data = $this->get_product_data( $which_plan );

		if ( isset( $product_data['post_title'] ) ) {

			$product_id = wp_insert_post( array(
				'post_author' => get_current_user_id(),
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_name'   => sanitize_text_field( $product_slug ),
				'post_title'  => sanitize_text_field( $product_data['post_title'] ),
			) );

			if ( is_numeric( $product_id ) ) {

				$product = new \WC_Product_Simple( $product_id );

				$product->set_price( isset( $product_data['price'] ) ? (float) $product_data['price'] : 0 );
				$product->save();
			}
		}

		return $product;
	}


	/**
	 * Returns product data used by the generator.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_product product slug
	 * @return array|null
	 */
	private function get_product_data( $which_product ) {

		$product = $this->remove_prefix( $which_product );
		$data    = $this->get_products_data();

		return ! empty( $data[ $product ] ) ? $data[ $product ] : array();
	}


	/**
	 * Returns data for products to be created in bulk.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_products_data() {

		return array(
			'simple-product-a' => array(
				'post_title' => __( 'Simple Product A', 'woocommerce-dev-helper' ),
				'price' => 1,
			),
			'simple-product-b' => array(
				'post_title' => __( 'Simple Product B', 'woocommerce-dev-helper' ),
				'price' => 10
			),
			'simple-product-c' => array(
				'post_title' => __( 'Simple Product C', 'woocommerce-dev-helper' ),
				'price'      => 19.80,
			),
		);
	}


	/**
	 * Records newly created objects to the job.
	 *
	 * This allows to save them to an option when the job has completed and let the bulk destroyer eliminate them when engaged.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_Membership_Plan $membership_plan plan object
	 * @param \stdClass $job background job object
	 * @return \stdClass modified job
	 */
	private function record_created_objects( $membership_plan, $job ) {

		// TODO
		return $job;
	}


	/**
	 * Adds a prefix to a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug a string which may already include a prefix, or not
	 * @return string
	 */
	private function add_prefix( $slug ) {

		return 0 !== strpos( $slug, $this->prefix ) ? "{$this->prefix}_{$slug}" : $slug;
	}


	/**
	 * Removes a prefix from a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug a string which may or may not include the prefix already
	 * @return string
	 */
	private function remove_prefix( $slug ) {

		return 0 === strpos( $slug, "{$this->prefix}_" ) ? substr( $slug, strlen( "{$this->prefix}_" ) ) : $slug;
	}


}
