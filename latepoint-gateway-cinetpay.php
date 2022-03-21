<?php
/**
 * Plugin Name: LatePoint Addon - Cinetpay
 * Plugin URI:  https://latepoint.com/
 * Description: LatePoint addon Cinetpay template
 * Version:     1.0.0
 * Author:      Gildas Kota
 * Author URI:  https://latepoint.com/
 * Text Domain: latepoint-gateway-cinetpay
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

// If no LatePoint class exists - exit, because LatePoint plugin is required for this addon

if ( ! class_exists( 'LatePoint_Gateway_Cinetpay' ) ) :

/**
 * Main Addon Class.
 *
 */

class LatePoint_Gateway_Cinetpay {

  /**
   * Addon version.
   *
   */
  public $version = '1.0.0';
  public $db_version = '1.0.0';
  public $addon_name = 'latepoint-gateway-cinetpay';
  public $processor_code = 'cinetpay';


  /**
   * LatePoint Constructor.
   */
  public function __construct() {
    $this->define_constants();
    $this->init_hooks();
  }

  /**
   * Define LatePoint Constants.
   */
  public function define_constants() {
    // $this->define( 'LATEPOINT_ADDON_EXAMPLE_CONSTANT', 'example' );
  }


  public static function public_stylesheets() {
    return plugin_dir_url( __FILE__ ) . 'public/stylesheets/';
  }

  public static function public_javascripts() {
    return plugin_dir_url( __FILE__ ) . 'public/javascripts/';
  }

  public static function images_url() {
    return plugin_dir_url( __FILE__ ) . 'public/images/';
  }

  /**
   * Define constant if not already set.
   *
   */
  public function define( $name, $value ) {
    if ( ! defined( $name ) ) {
      define( $name, $value );
    }
  }

  /**
   * Include required core files used in admin and on the frontend.
   */
  public function includes() {

    // CONTROLLERS
    include_once( dirname( __FILE__ ) . '/lib/controllers/gateway_cinetpay_controller.php' );

    // HELPERS
    include_once( dirname( __FILE__ ) . '/lib/helpers/gateway_cinetpay_helper.php' );

    // MODELS
    // include_once(dirname( __FILE__ ) . '/lib/models/example_model.php' );

  }


  public function init_hooks(){
    // Hook into the latepoint initialization action and initialize this addon
    add_action('latepoint_init', [$this, 'latepoint_init']);

    // Include additional helpers and controllers 
    add_action('latepoint_includes', [$this, 'includes']);


    // add settings fields for the payment processor
    add_action('latepoint_payment_processor_settings',[$this, 'add_settings_fields'], 10);

    // Modify a list of installed add-ons
    add_filter('latepoint_installed_addons', [$this, 'register_addon']);

    // Register cinetpay as a payment processor
    add_filter('latepoint_payment_processors', [$this, 'register_payment_processor'], 10, 2);
		// Register cinetpay available payment methods
		add_filter('latepoint_all_payment_methods', [$this, 'register_payment_methods']);
		// Add payment methods to a list of enabled methods for the front-end, if processor is turned on in settings
		add_filter('latepoint_enabled_payment_methods', [$this, 'register_enabled_payment_methods']);

     // encrypt sensitive fields
     add_filter('latepoint_encrypted_settings', [$this, 'add_encrypted_settings']);

    // pass variables to JS frontend
    add_filter('latepoint_localized_vars_front', [$this, 'localized_vars_for_front']);

    // hooks into the action to enqueue our styles and scripts
    add_action('latepoint_wp_enqueue_scripts', [$this, 'load_front_scripts_and_styles']);

    // hook into payment processing
    add_filter('latepoint_process_payment_for_booking', [$this, 'process_payment'], 10, 3);

    // Add a link to the side menu
    // add_filter('latepoint_side_menu', [$this, 'add_menu_links']);

    // Include JS and CSS for the admin panel
    // add_action('latepoint_admin_enqueue_scripts', [$this, 'load_admin_scripts_and_styles']);

    // Include JS and CSS for the frontend site
    // add_action('latepoint_wp_enqueue_scripts', [$this, 'load_front_scripts_and_styles']);

    // All other hooks can go here, for a list of available hoooks go to: http://wpdocs.latepoint.com/list-of-latepoint-hooks-actions-and-filters/
    // add_action('EXAMPLE_HOOK', [$this, 'EXAMPLE_FUNCTION']);

    // init the addon
    add_action( 'init', array( $this, 'init' ), 0 );

    register_activation_hook(__FILE__, [$this, 'on_activate']);
    register_deactivation_hook(__FILE__, [$this, 'on_deactivate']);
  }


  // Payment method for the processor
	public function get_supported_payment_methods(){
		return ['inline_checkout' => [
				'name' => __('Inline Checkout', 'latepoint-gateway-cinetpay'), 
				'label' => __('Inline Checkout', 'latepoint-gateway-cinetpay'), 
				'image_url' => LATEPOINT_IMAGES_URL.'cinetpay.png',
						'code' => 'inline_checkout',
						'time_type' => 'now'
					]
		];
	}

  // register payment processor
	public function register_payment_processor($payment_processors, $enabled_only){
		$payment_processors[$this->processor_code] = ['code' => $this->processor_code, 
																									'name' => __('Cinetpay', 'latepoint-gateway-cinetpay'), 
																									'image_url' => $this->images_url().'cinetpay.png'];
		return $payment_processors;
	}



	// adds payment method to payment settings
	public function register_payment_methods($payment_methods){
		$payment_methods = array_merge($payment_methods, $this->get_supported_payment_methods());
		return $payment_methods;
	}

  public function add_settings_fields($processor_code){
    if($processor_code != $this->processor_code) return false; ?>
      <h3 class="os-sub-header"><?php _e('API Keys', 'latepoint-gateway-cinetpay'); ?></h3>
      <div class="os-row">
        <div class="os-col-6">
          <?php echo OsFormHelper::text_field('settings[cinetpay_publishable_key]', __('Site ID', 'latepoint-gateway-cinetpay'), OsSettingsHelper::get_settings_value('cinetpay_publishable_key')); ?>
        </div>
        <div class="os-col-6">
          <?php echo OsFormHelper::password_field('settings[cinetpay_secret_key]', __('Secret Key', 'latepoint-gateway-cinetpay'), OsSettingsHelper::get_settings_value('cinetpay_secret_key')); ?>
        </div>
      </div>
      <h3 class="os-sub-header"><?php _e('Autres Paramètres', 'latepoint-gateway-cinetpay'); ?></h3>
      <div class="os-row">
        <div class="os-col-6">
          <?php echo OsFormHelper::select_field('settings[cinetpay_country_code]', __('Country', 'latepoint-gateway-cinetpay'), OsGatewayCinetpayHelper::load_countries_list(), OsSettingsHelper::get_settings_value('cinetpay_country_code', 'CI')); ?>
        </div>
        <div class="os-col-6">
          <?php echo OsFormHelper::select_field('settings[cinetpay_currency_iso_code]', __('Currency Code', 'latepoint-gateway-cinetpay'), OsGatewayCinetpayHelper::load_currencies_list(), OsSettingsHelper::get_settings_value('cinetpay_currency_iso_code', 'XOF')); ?>
        </div>
      </div>
      <div class="os-row">
        <div class="os-col-12">
          <?php echo OsFormHelper::media_uploader_field('settings[cinetpay_logo_image_id]', 0, __('Logo for Payment Modal', 'latepoint-gateway-cinetpay'), __('Remove Logo', 'latepoint-gateway-cinetpay'), OsSettingsHelper::get_settings_value('cinetpay_logo_image_id')); ?>
        </div>
      </div>
    <?php
  }

	// enables payment methods if the processor is turned on
	public function register_enabled_payment_methods($enabled_payment_methods){
		// check if payment processor is enabled in settings
		if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
			$enabled_payment_methods = array_merge($enabled_payment_methods, $this->get_supported_payment_methods());
		}
		return $enabled_payment_methods;
	}


  // Loads addon specific javascript and stylesheets for frontend site

  // Loads addon specific javascript and stylesheets for backend (wp-admin)
  public function load_admin_scripts_and_styles($localized_vars){
    // Stylesheets
    wp_enqueue_style( 'latepoint-gateway-cinetpay', $this->public_stylesheets() . 'latepoint-gateway-cinetpay-admin.css', false, $this->version );

    // Javascripts
    wp_enqueue_script( 'latepoint-gateway-cinetpay',  $this->public_javascripts() . 'latepoint-gateway-cinetpay-admin.js', array('jquery'), $this->version );
  }


  public function add_menu_links($menus){
    if(!OsAuthHelper::is_admin_logged_in()) return $menus;
    $menus[] = ['id' => 'addon_starter', 
                'label' => __( 'Example Link', 'latepoint-gateway-cinetpay' ), 
                'icon' => 'latepoint-icon latepoint-icon-play-circle', 
                'link' => OsRouterHelper::build_link(['example', 'view_example'])];
    return $menus;
  }




 public function add_encrypted_settings($encrypted_settings){
  $encrypted_settings[] = 'cinetpay_secret_key';
  return $encrypted_settings;
} 

public function localized_vars_for_front($localized_vars){
  // check if cinetpay is enabled
  if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
    $localized_vars['is_cinetpay_active'] = true;
    // pass variables from settings to frontend
    $localized_vars['cinetpay_key'] = OsSettingsHelper::get_settings_value('cinetpay_publishable_key', '');
    $localized_vars['cinetpay_payment_options_route'] = OsRouterHelper::build_route_name('gateway_cinetpay', 'get_payment_options');
  }else{
    $localized_vars['is_cinetpay_active'] = false;
  }
  return $localized_vars;
}

  // Loads addon specific javascript and stylesheets for frontend site
  public function load_front_scripts_and_styles(){

    // Stylesheets

    wp_enqueue_style( 'latepoint-gateway-cinetpay-front', $this->public_stylesheets() . 'latepoint-gateway-cinetpay-front.css', false, $this->version );

    // Javascripts

    // add cinetpay library
    wp_enqueue_script( 'cinetpay-checkout', 'https://api-checkout.cinetpay.com/%s/payment', false, null );
    // include our custom js file with payment init methods
    wp_enqueue_script( 'latepoint-gateway-cinetpay-front',  $this->public_javascripts() . 'latepoint-gateway-cinetpay-front.js', array('jquery', 'cinetpay-checkout', 'latepoint-main-front'), $this->version );
  }



  public function process_payment($result, $booking, $customer){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      switch($booking->payment_method){
        // check if payment method is cinetpay inline checkout
        case 'inline_checkout':
          if($booking->payment_token){
            // call cinetpay api endpoint to verify that transaction exists
            $remote = wp_remote_get( "https://new-api.cinetpay.ci/v2/countries/".$booking->payment_token."/verify", [
                        'timeout' => 10,
                        'headers' => [
                          'Accept' => 'application/json',
                          'Authorization' => 'Bearer '. self::get_secret_key()
                        ]
                      ]);
            // process the response
            if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
              $response_body = json_decode($remote['body']);
              // check if transaction is found and it has "successful" status
              if($response_body->status == 'success' && $response_body->data->status == 'successful'){
                $result['status'] = LATEPOINT_STATUS_SUCCESS;
                $result['charge_id'] = $response_body->data->id;
                $result['processor'] = $this->processor_code;
                $result['funds_status'] = LATEPOINT_TRANSACTION_FUNDS_STATUS_CAPTURED;
              }else{
                // transaction not found or is not successfull
                $result['status'] = LATEPOINT_STATUS_ERROR;
                $result['message'] = __('Payment Error', 'latepoint-gateway-cinetpay');
                $booking->add_error('payment_error', $result['message']);
                $booking->add_error('send_to_step', $result['message'], 'payment');
              }
            }else{
              // api connection error 
              $result['status'] = LATEPOINT_STATUS_ERROR;
              $result['message'] = __('Connection error', 'latepoint-gateway-cinetpay');
              $booking->add_error('payment_error', $result['message']);
              $booking->add_error('send_to_step', $result['message'], 'payment');
            }
          }else{
            // payment token is not set
            $result['status'] = LATEPOINT_STATUS_ERROR;
            $result['message'] = __('Payment Error KSF9834', 'latepoint-gateway-cinetpay');
            $booking->add_error('payment_error', $result['message']);
          }
        break;
      }
    }
    return $result;
  }
  /**
   * Init addon when WordPress Initialises.
   */
  public function init() {
    // Set up localisation.
    $this->load_plugin_textdomain();
  }

  public function latepoint_init(){
    LatePoint\Cerber\Router::init_addon();
  }


  // set text domain for the addon, for string translations to work
  public function load_plugin_textdomain() {
    load_plugin_textdomain('latepoint-gateway-cinetpay', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }


  public function on_deactivate(){
  }

  public function on_activate(){
    do_action('latepoint_on_addon_activate', $this->addon_name, $this->version);
  }

  public function register_addon($installed_addons){
    $installed_addons[] = ['name' => $this->addon_name, 'db_version' => $this->db_version, 'version' => $this->version];
    return $installed_addons;
  }



}

endif;

if ( in_array( 'latepoint/latepoint.php', get_option( 'active_plugins', array() ) )  || array_key_exists('latepoint/latepoint.php', get_site_option('active_sitewide_plugins', array())) ) {
  $LATEPOINT_ADDON_GATEWAY_CINETPAY = new LatePoint_Gateway_Cinetpay();
}