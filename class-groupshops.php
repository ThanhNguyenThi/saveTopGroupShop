<?php

/**
 * Plugin Name:         Group Shops
 * Plugin URI:          http://topgroupshops.com.vn/
 * Description:         Allow vendors to sell their own products and receive a commission for each sale. 
 * Author:              Thanh Nguyen
 * Author URI:          http://topgroupshops.com.vn/
 * GitHub Plugin URI:   https://github.com/ThanhNguyenThi/saveTopGroupShop
 *
 * Version:             1.0.0
 * Requires at least:   4.4.0
 * Tested up to:        4.7.1
 *
 * Text Domain:         topgroupshops
 * Domain Path:         /languages/
 *
*/


/**
 *   Plugin activation hook 
 */
function topgroupshops_activate() {

	/**
	 *  Requires woocommerce to be installed and active 
	 */
	if ( !class_exists( 'WooCommerce' ) ) { 
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'Group Shops requires WooCommerce to run. Please install WooCommerce and activate before attempting to activate again.', 'topgroupshops' ) );
	}
} // topgroupshops_activate()

register_activation_hook( __FILE__, 'topgroupshops_activate' );


/**
 * Required functions
 */
require_once trailingslashit( dirname( __FILE__ ) ) . 'classes/includes/class-functions.php';

/**
 * Check if WooCommerce is active
 */
if ( tgs_is_woocommerce_activated() ) {

	/* Define an absolute path to our plugin directory. */
	if ( !defined( 'tgs_plugin_dir' ) ) 		define( 'tgs_plugin_dir', trailingslashit( dirname( __FILE__ ) ) . '/' );
	if ( !defined( 'tgs_assets_url' ) ) 		define( 'tgs_assets_url', trailingslashit( plugins_url( 'assets', __FILE__ ) ) );
	if ( !defined( 'tgs_plugin_base' ) ) 		define( 'tgs_plugin_base', plugin_basename( __FILE__ ) );
	if ( !defined( 'tgs_plugin_dir_path' ) )	define( 'tgs_plugin_dir_path', untrailingslashit( plugin_dir_path( __FILE__ ) ) );


	define('TGS_VERSION', '1.9.7' ); 

	/**
	 * Main Product Vendor class
	 *
	 * @package TGSVendors
	 */
	class TGS_Vendors
	{

		/**
		 * @var
		 */
		public static $pv_options;
		public static $id = 'wc_prd_vendor';

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			
			// Load text domain 
			add_action( 'plugins_loaded', array( $this, 'load_il8n' ) );

			$this->title = __( 'Group Shops', 'topgroupshops' );

			// Install & upgrade
			add_action( 'admin_init', array( $this, 'check_install' ) );
			add_action( 'admin_init', array( $this, 'maybe_flush_permalinks' ), 99 );
			add_action( 'admin_init', array( $this, 'tgs_required_ignore_notices' ) );

			add_action( 'plugins_loaded', array( $this, 'load_settings' ) );
			add_action( 'plugins_loaded', array( $this, 'include_gateways' ) );
			add_action( 'plugins_loaded', array( $this, 'include_core' ) ); 
			add_action( 'init', 		  array( $this, 'include_init' ) ); 
			add_action( 'current_screen', array( $this, 'include_assets' ) ); 

			add_filter( 'plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2 );
			add_action( self::$id . '_options_updated', array( $this, 'option_updates' ), 10, 2 );

			// Start a PHP session, if not yet started then destroy if logged in or out 
			add_action( 'init', 		array( $this, 'init_session'), 1 ); 
			add_action( 'wp_logout', 	array( $this, 'destroy_session') );
			add_action( 'wp_login', 	array( $this, 'destroy_session') );
		}


		/**
		 *
		 */
		public function invalid_wc_version()
		{
			echo '<div class="error"><p>' . __( '<b>Group Shops is disabled</b>. Group Shops requires a minimum of WooCommerce v2.5.0.', 'topgroupshops' ) . '</p></div>';
		}

		/**
		 *  Start the session 
		 */
		public function init_session(){ 
			
			 if ( !session_id() && is_user_logged_in() ) {
        		session_start();
    		 }

		} //init_session() 

		public function destroy_session(){ 

			 if ( session_id() ) {
        		session_destroy(); 
    		 }

		} // destroy_session()


		/**
		 * Check whether install has ran before or not
		 *
		 * Run install if it hasn't.
		 *
		 * @return unknown
		 */
		public function check_install()
		{
			global $woocommerce;

			// WC 2.5.0+ is required
			if ( version_compare( $woocommerce->version, '2.5', '<' ) ) {
				add_action( 'admin_notices', array( $this, 'invalid_wc_version' ) );
				deactivate_plugins( plugin_basename( __FILE__ ) );
				return false;
			}

			require_once tgs_plugin_dir . 'classes/class-install.php';

			$this->load_settings();
			$install = new TGS_Install;
			$install->init();
		}


		/**
		 * Set static $pv_options to hold options class
		 */
		public function load_settings()
		{
			if ( empty( self::$pv_options ) ) {
				require_once tgs_plugin_dir . 'classes/admin/settings/classes/sf-class-settings.php';
				self::$pv_options = new SF_Settings_API( self::$id, $this->title, 'woocommerce', __FILE__ );
				self::$pv_options->load_options( tgs_plugin_dir . 'classes/admin/settings/sf-options.php' );
			}
		}

		public function load_il8n() { 

			$domain = 'topgroupshops';
		    
		    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		    //Place your custom translations into wp-content/languages/wc-vendors to be upgrade safe 
		    load_textdomain($domain, WP_LANG_DIR.'/wc-vendors/'.$domain.'-'.$locale.'.mo');
			
			load_plugin_textdomain( 'topgroupshops', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		}


		/**
		 * Include core files
		 */
		public function include_core()
		{
			require_once tgs_plugin_dir . 'classes/class-queries.php';
			require_once tgs_plugin_dir . 'classes/class-vendors.php';
			require_once tgs_plugin_dir . 'classes/class-cron.php';
			require_once tgs_plugin_dir . 'classes/class-commission.php';
			require_once tgs_plugin_dir . 'classes/class-shipping.php';
			require_once tgs_plugin_dir . 'classes/class-vendor-order.php';
			require_once tgs_plugin_dir . 'classes/class-vendor-post-types.php'; 
			require_once tgs_plugin_dir . 'classes/front/class-vendor-cart.php';
			require_once tgs_plugin_dir . 'classes/front/dashboard/class-vendor-dashboard.php';
			require_once tgs_plugin_dir . 'classes/front/class-vendor-shop.php';
			require_once tgs_plugin_dir . 'classes/front/signup/class-vendor-signup.php';
			require_once tgs_plugin_dir . 'classes/front/orders/class-orders.php';
			require_once tgs_plugin_dir . 'classes/admin/emails/class-emails.php';
			require_once tgs_plugin_dir . 'classes/admin/class-product-meta.php';
			require_once tgs_plugin_dir . 'classes/admin/class-vendor-applicants.php';
			require_once tgs_plugin_dir . 'classes/admin/class-vendor-reports.php';
			require_once tgs_plugin_dir . 'classes/admin/class-admin-reports.php';
			require_once tgs_plugin_dir . 'classes/admin/class-admin-users.php';
			require_once tgs_plugin_dir . 'classes/admin/class-admin-page.php';
			require_once tgs_plugin_dir . 'classes/admin/class-vendor-admin-dashboard.php'; 
			require_once tgs_plugin_dir . 'classes/includes/class-tgs-shortcodes.php';


			if ( !function_exists( 'woocommerce_wp_text_input' ) && !is_admin() ) {
				include_once(WC()->plugin_path() . '/includes/admin/wc-meta-box-functions.php');
			}

			new TGS_Vendors;
			new TGS_Vendor_Shop;
			new TGS_Vendor_Cart;
			new TGS_Commission;
			new TGS_Shipping;
			new TGS_Cron;
			new TGS_Orders;
			new TGS_Vendor_Dashboard;
			new TGS_Admin_Setup;
			new TGS_Vendor_Admin_Dashboard; 
			new TGS_Admin_Reports;
			new TGS_Vendor_Applicants;
			new TGS_Emails;
			new TGS_Vendor_Signup;
			new TGS_Shortcodes; 
		}


		/**
		 * These need to be initlized later in loading to fix interaction with other plugins that call current_user_can at the right time. 
		 * 
		 * @since 1.9.4 
		 * @access public 
		 */
		public function include_init(){ 

			new TGS_Vendor_Reports;
			new TGS_Product_Meta;
			new TGS_Admin_Users;

		} // include_init() 

		/** 
		*	Load plugin assets 
		*/ 
		public function include_assets(){

			$screen = get_current_screen(); 

			if ( in_array( $screen->id, array( 'edit-product' ) ) ) {
				wp_enqueue_script( 'tgs_quick-edit', tgs_assets_url. 'js/tgs-admin-quick-edit.js', array('jquery') );
			}

		}


		/**
		 * Include payment gateways
		 */
		public function include_gateways()
		{
			require_once tgs_plugin_dir . 'classes/gateways/PayPal_AdvPayments/paypal_ap.php';
			require_once tgs_plugin_dir . 'classes/gateways/PayPal_Masspay/class-paypal-masspay.php';
			require_once tgs_plugin_dir . 'classes/gateways/TGS_Gateway_Test/class-tgs-gateway-test.php';
		}


		/**
		 * Do an action when options are updated
		 *
		 * @param array   $options
		 * @param unknown $tabname
		 */
		public function option_updates( $options, $tabname )
		{
			// Change the vendor role capabilities
			if ( $tabname == sanitize_title(__( 'Capabilities', 'topgroupshops' )) ) {
				$can_add          = $options[ 'can_submit_products' ];
				$can_edit         = $options[ 'can_edit_published_products' ];
				$can_submit_live  = $options[ 'can_submit_live_products' ];
				$can_view_reports = $options[ 'can_view_backend_reports' ];

				$args = array(
					'assign_product_terms'      => $can_add,
					'edit_products'             => $can_add || $can_edit,
					'edit_published_products'   => $can_edit,
					'delete_published_products' => $can_edit,
					'delete_products'           => $can_edit,
					'manage_product'            => $can_add,
					'publish_products'          => $can_submit_live,
					'read'                      => true,
					'read_products'             => $can_edit || $can_add,
					'upload_files'              => true,
					'import'                    => true,
					'view_woocommerce_reports'  => false,
				);

				remove_role( 'vendor' );
				
				add_role( 'vendor', __('Vendor', 'topgroupshops'), $args );
			} // Update permalinks
			else if ( $tabname == sanitize_title(__( 'General', 'topgroupshops' ) )) {
				$old_permalink = TGS_Vendors::$pv_options->get_option( 'vendor_shop_permalink' );
				$new_permalink = $options[ 'vendor_shop_permalink' ];

				if ( $old_permalink != $new_permalink ) {
					update_option( TGS_Vendors::$id . '_flush_rules', true );
				}
			}

			do_action( 'topgroupshops_option_updates', $options, $tabname ); 

		}


		/**
		 *  If the settings are updated and the vendor page link has changed update permalinks 
		 *	@access public
		 *
		*/
		public function maybe_flush_permalinks()
		{
			if ( get_option( TGS_Vendors::$id . '_flush_rules' ) ) {
				flush_rewrite_rules();
				update_option( TGS_Vendors::$id . '_flush_rules', false );
			}
		}

		/**
		 *  Add links to plugin page to our external help site. 
		 *	@param $links - links array from action 
		 *	@param $file - file reference for this plugin 
		 *	@access public 
		 * 
		 */
		public static function plugin_row_meta( $links, $file ) {
			if ( $file == tgs_plugin_base ) {

				$row_meta = array(
	                            'docs' 		=> '<a href="http://www.topgroupshops.com/kb/" target="_blank">'.__( 'Documentation/KB', 'topgroupshops' ).'</a>',
	                            'help' 		=> '<a href="http://www.topgroupshops.com/help/" target="_blank">'.__( 'Help Forums', 'topgroupshops').'</a>',
	                            'support' 	=> '<a href="http://www.topgroupshops.com/contact-us/" target="_blank">'.__( 'Paid Support', 'topgroupshops' ).'</a>'
	                        );

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		/**
		 * Add user meta to remember ignore notices 
		 * @access public
         * 
		 */
		public function tgs_required_ignore_notices(){
			global $current_user;
    		$current_user_id = $current_user->ID;
    		
	        /* If user clicks to ignore the notice, add that to their user meta */
	        if ( isset( $_GET[ 'tgs_shop_ignore_notice' ] ) && '0' == $_GET[ 'tgs_shop_ignore_notice' ] ) {
	            add_user_meta( $current_user_id, 'tgs_shop_ignore_notice', 'true', true);
	    	}				
			if ( isset($_GET['tgs_pl_ignore_notice']) && '0' == $_GET['tgs_pl_ignore_notice'] ) {
			 	add_user_meta( $current_user_id, 'tgs_pl_ignore_notice', 'true' , true);
			}
		}


	}


	$tgs_vendors = new TGS_Vendors;

}
