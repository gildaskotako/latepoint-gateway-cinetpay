<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}


if ( ! class_exists( 'OsGatewayCinetpayController' ) ) :
  
  class OsGatewayCinetpayController extends OsController {

    function __construct(){
      parent::__construct();
      $this->views_folder = plugin_dir_path( __FILE__ ) . '../views/cinetpay/';
    }


    /* Generates payment options for cinetpay inline checkout */
    public function get_payment_options(){
      // set booking object from passed params
      OsStepsHelper::set_booking_object($this->params['booking']);
      // set restrictions passed from a form shortcode
      OsStepsHelper::set_restrictions($this->params['restrictions']);

      $customer = OsAuthHelper::get_logged_in_customer();
      // calculate amount to be charged
      $amount = OsStepsHelper::$booking_object->specs_calculate_price_to_charge();
      
      try{
        if($amount > 0){
          // create booking intent in the database
          $booking_intent = OsBookingIntentHelper::create_or_update_booking_intent($this->params['booking'], $this->params['restrictions'], ['payment_method' => $this->params['booking']['payment_method']], '');
          // create options array, which will be passed to the front-end JS
          $options = [
            "public_key" => OsSettingsHelper::get_settings_value('cinetpay_publishable_key'),
            "tx_ref" => $booking_intent->intent_key,
            "amount" => $amount,
            "currency" => OsSettingsHelper::get_settings_value('cinetpay_currency_iso_code', 'NGN'),
            "country" => OsSettingsHelper::get_settings_value('cinetpay_country_code', 'NG'),
            "customer" => [
                "email" => $customer->email,
                "phone_number" => $customer->phone,
                "name" => $customer->full_name
              ]
            ,
            "customizations" => [
                "name" => OsSettingsHelper::get_settings_value('cinetpay_company_name', 'Company'),
                "description" => $booking->service->name,
                "logo" => OsImageHelper::get_image_url_by_id(OsSettingsHelper::get_settings_value('cinetpay_logo_image_id', false))
              ]
          ];
          $this->send_json(array('status' => LATEPOINT_STATUS_SUCCESS, 'options' => $options, 'amount' => $amount, 'booking_intent_key' => $booking_intent->intent_key));
        }else{
          // free booking, nothing to pay (probably coupon was applied)
          $this->send_json(array('status' => LATEPOINT_STATUS_SUCCESS, 'message' => __('Nothing to pay', 'latepoint-gateway-cinetpay'), 'amount' => $amount));
        }
      }catch(Exception $e){
        error_log($e->getMessage());
        $this->send_json(array('status' => LATEPOINT_STATUS_ERROR, 'message' => $e->getMessage()));
      }
    }
  }

endif;

