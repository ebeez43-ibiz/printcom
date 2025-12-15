<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once WC_PRINT_PLUGIN_DIR . '/vendor/cmb2/init.php';
require_once WC_PRINT_PLUGIN_DIR . '/vendor/cmb2/includes/CMB2_Tabs.php';

class WC_Print_Admin_Setting {

	/**
	 * Parent plugin class
	 *
	 * @var    class
	 * @since  NEXT
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $key = 'wc_print_settings';

	/**
	 * Options page metabox id
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $metabox_id = 'wc_print_settings_metabox';

	/**
	 * Options Page title
	 *
	 * @var    string
	 * @since  NEXT
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 *
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Saved options.
	 */
	public $options = '';

	/**
	 * Constructor
	 *
	 * @since  NEXT
	 *
	 * @param  object $plugin Main plugin object.
	 *
	 * @return void
	 */
	public function __construct(  ) {

		$this->hooks();
		$this->title   = __( 'WooCommerces Print', 'wc-print');
		$this->options = get_option('wc_print_settings');
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'woocommerce_add_screen_ids' ),10,1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'wp_ajax_wc_print_import_product',                 array($this, 'import_product') );
        add_action( 'wp_ajax_wc_print_sent_order',  array($this, 'sent_order') );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function admin_init() {
		register_setting( $this->key, $this->key );
	}

     /**
     * @return void
     */
    public function sent_order(){
       
        check_ajax_referer('wc-print-admin-security', 'nonce');
        $order_id= $_POST['order_id'];
        $response['success'] = false;
        if(empty($order_id)){
            $response['message'] = __('Order must not be empty', 'wc-print');
            wp_send_json($response);wp_die();
        }

        $order    = wc_get_order( $order_id );
        if ( ( empty( $order ) || !  $order instanceof \WC_Order) ) {
            $response['message'] = __('Invalid Woocommerce Order', 'wc-print');
            wp_send_json($response);wp_die();
        }
        $WC_Print_Order_Sync = new WC_Print_Order_Sync();
        $response= $WC_Print_Order_Sync->sent_order($order);
        wp_send_json($response);wp_die();
    }

    public function import_product(){
        check_ajax_referer('wc-print-admin-security', 'nonce');
        $sku = $_POST['sku'];
        $response['success'] = false;
        if(empty($sku)){
           $response['message'] = __('Product sku must not be empty', 'wc-print');
           wp_send_json($response);wp_die();
       }

        $WC_Print_Product_Sync = new WC_Print_Product_Sync();
        $response= $WC_Print_Product_Sync->sync_product($sku);
        wp_send_json($response);wp_die();
    }
	public function admin_scripts() {
			global $wp_query, $post;

			$screen       = get_current_screen();
			$screen_id    = $screen ? $screen->id : '';
         	$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $allow_pages=['edit-shop_order','toplevel_page_wc_print_settings','product'];
			if(in_array($screen_id,$allow_pages)){
                wp_register_style( 'wc_print_settings_css', WC_PRINT_PLUGIN_URL.'/assets/admin/css/wc-print.css?v='.time() );
                wp_enqueue_style( 'wc_print_settings_css' );
				wp_register_script( 'wc_print_settings_js', WC_PRINT_PLUGIN_URL.'/assets/admin/js/wc-print.js?v='.time(), array( 'jquery' ) );
				wp_enqueue_script( 'wc_print_settings_js' );
				wp_localize_script('wc_print_settings_js', 'wc_print_settings_js', array(
				'i18n_remove_warning' => __('\'Are you sure you want to remove this section?', 'wc-print'),
                'empty_sku' => __('Sku must not be empty', 'wc-print'),
                 'ajax_nonce' =>    wp_create_nonce('wc-print-admin-security'),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
                'screen_id' =>$screen_id
				));
			}
	}

 	public function woocommerce_add_screen_ids($screen_ids){
	 $screen    = get_current_screen();
	 	$screen_ids[]='toplevel_page_wc_print_settings';
		return $screen_ids;
    }
	/**
	 * Add menu options page
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function add_options_page() {
        $this->options_page = add_menu_page(
			$this->title,
			$this->title,
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' ),
            WC_PRINT_PLUGIN_URL.'assets/img/logo-32_32.png?v='.time()
		);

		// Include CMB CSS in the head to avoid FOUC.
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
        <div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>" id="<?php echo esc_attr( $this->key ); ?>_sync_product">
            <?php require_once WC_PRINT_ADMIN_TEMPLATE .'settings/sync-product.php'?>
        </div>
		<?php
	}
	/**
	 * Add custom fields to the options page.
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function add_options_page_metabox() {

		$cmb = new_cmb2_box(array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove.
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
			'title'         => esc_html__('WooCommerce Print Settings', 'wc-print'),
			'object_types'  => array('page'), // Post type
			'tabs'          => array(

				'print_api'     => array(
					'label'     => __('Print API Settings', 'wc-print'),
					'icon'      => 'dashicons-admin-users', // Dashicon
				),
                'address'  => array(
                    'label' => __('Default Shipping Address ', 'wc-print'),
                    'icon'  => 'dashicons-location', // Dashicon
                ),
                'general'  => array(
                    'label' => __('General ', 'wc-print'),
                    'icon'  => 'dashicons-shield', // Dashicon
                ),

                'order'  => array(
                    'label' => __('Order ', 'wc-print'),
                    'icon'  => 'dashicons-cart', // Dashicon
                ),
                'fees'  => array(
                    'label' => __('Fees ', 'wc-print'),
                    'icon'  => 'dashicons-cart', // Dashicon
                ),
                'hidden_fields'  => array(
                    'label' => __('Hidden print fields ', 'wc-print'),
                    'icon'  => 'dashicons-cart', // Dashicon
                ),
                
        )));

		$cmb->add_field( array(
            'name'   =>     esc_html__( 'API Username', 'wc-print'),
			'id'          => 'credentials_username',
			'type'        => 'text',
			'tab'           => 'print_api',
			'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
			'description' => esc_html__( 'Print API Username', 'wc-print'),

		) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'API Password', 'wc-print'),
            'id'             => 'credentials_password',
            'type'          => 'text',
            'tab'           => 'print_api',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description'   => esc_html__( 'Print API Password', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'API Url', 'wc-print'),
            'id'          => 'api_url',
            'type'           => 'text_url',
            'tab'           => 'print_api',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Print API Url', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Default Image', 'wc-print'),
            'id'            => 'default_image',
            'type'          => 'file',
            'tab'           => 'general',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Default Products Image', 'wc-print'),
        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Default Category', 'wc-print'),
            'id'            => 'default_category',
            'taxonomy'       => 'product_cat', //Enter Taxonomy Slug
            'type'           => 'taxonomy_select',
            'remove_default' => 'true', // Removes the default metabox provided by WP core.
            // Optionally override the args sent to the WordPress get_terms function.
            'query_args' => array(
                // 'orderby' => 'slug',
                // 'hide_empty' => true,
            ),
            'tab'           => 'general',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Default Products Category', 'wc-print'),
        ) );
        // Default Shipping Address
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'First name', 'wc-print'),
            'id'          => 'shipping_first_name',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address first name', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Last name', 'wc-print'),
            'id'          => 'shipping_last_name',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address last name', 'wc-print'),

        ) );
        $shipping_countries = WC()->countries->get_shipping_countries();
        $shipping_countries['NL'] = __( 'Netherlands', 'woocommerce' );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Country', 'wc-print'),
            'id'          => 'shipping_country',
            'type'        => 'select',
            'tab'           => 'address',
            'options'      =>$shipping_countries,
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address country', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'City', 'wc-print'),
            'id'          => 'shipping_city',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address city', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Company', 'wc-print'),
            'id'          => 'shipping_company',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address company', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Address line 1', "woocommerce" ),
            'id'          => 'shipping_address_1',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address postcode', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Address line 2', "woocommerce" ),
            'id'          => 'shipping_address_2',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address postcode', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Postcode / ZIP', 'wc-print'),
            'id'          => 'shipping_postcode',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address postcode', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'E-mail', "woocommerce" ),
            'id'          => 'shipping_email',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address email', 'wc-print'),

        ) );
        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Phone', "woocommerce" ),
            'id'          => 'shipping_phone',
            'type'        => 'text',
            'tab'           => 'address',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Shipping address phone', 'wc-print'),

        ) );
        // End Shipping Address

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Delivery Trigger', 'wc-print'),
            'id'            => 'delivery_trigger',
            'type'           => 'select',
            'options'   => wc_get_order_statuses(),
            'tab'           => 'order',
            'default'          => 'wc-completed',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Send to Print API Trigger', 'wc-print'),
        ) );

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Cancel Trigger', 'wc-print'),
            'id'            => 'cancel_trigger',
            'type'           => 'select',
            'options'   => wc_get_order_statuses(),
            'default'          => 'wc-cancelled',
            'tab'           => 'order',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Cancel from Print API Trigger', 'wc-print'),
        ) );

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Delivery Price', 'wc-print'),
            'id'             => 'deliver_price',
            'type'          => 'text',
            'tab'           => 'fees',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description'   => esc_html__( 'Delivery Price', 'wc-print'),

        ) );

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Commission', 'wc-print'),
            'id'          => 'commission',
            'type'           => 'text',
            'tab'           => 'fees',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Commission', 'wc-print'),
        ) );

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Commission type', 'wc-print'),
            'id'          => 'commission_type',
            'type'       => 'select',
            'default'     => 'percentage',
            'options'     => array(''=>__('Choose type','wc-print'), 'percentage'=>__('Percentage','wc-print'), 'fixed_price'=>__('Fixed price','wc-print')),
            'tab'        => 'fees',
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Commission type', 'wc-print'),
        ) );
        $hidden_fields= [];

        $wc_print_fields          =   get_option('_wc_print_fields');
            if (!empty($wc_print_fields )) {
            foreach ($wc_print_fields as $slug => $name){
                $hidden_fields[$slug] = $name;
            }
        }
        

        $cmb->add_field( array(
            'name'   =>     esc_html__( 'Show/Hide fields', 'wc-print'),
            'id'          => 'wc_print_hidden_fields',
            'type'           => 'multicheck',
            'tab'           => 'hidden_fields',
            'options'     =>    $hidden_fields,
            'multiple'    => true,
            'render_row_cb' => array('CMB2_Tabs', 'tabs_render_row_cb'),
            'description' => esc_html__( 'Show/Hide fields', 'wc-print'),
        ) );

    }

}


new WC_Print_Admin_Setting();

