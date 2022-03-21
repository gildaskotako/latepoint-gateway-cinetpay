<?php  

class OsGatewayCinetpayHelper {
  public static $processor_name = 'cinetpay';
  public static $default_currency_iso_code = 'XOF';

  public static $paystack = false;


  public static function get_currency_iso_code(){
    return OsSettingsHelper::get_settings_value('paystack_currency_iso_code', self::$default_currency_iso_code);
  }

	public static function get_publishable_key(){
		return OsSettingsHelper::get_settings_value('paystack_publishable_key', '');
	}

	public static function get_secret_key(){
		return OsSettingsHelper::get_settings_value('paystack_secret_key');
	}

	public static function set_api_key(){
		if(self::get_secret_key()){
      \Paystack\Paystack::init(self::get_secret_key());
		}
	}


  public static function get_customer($paystack_customer_id){
    $customer = \Paystack\Customer::get($paystack_customer_id);
    if($customer){
      return $customer;
    }else{
      return false;
    }
  }

  private static function get_properties_allowed_to_update($roles = 'admin'){
    return array('email', 'first_name', 'last_name', 'phone');
  }

  public static function update_customer($paystack_customer_id, $values_to_update = array()){
  }

  public static function create_customer($customer){
    $customer = \Paystack\Customer::create([
        'email' => $customer->email,
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'phone' => $customer->phone,
    ]);
    if($customer){
      return $customer;
    }else{
      return false;
    }
  }

  public static function verify_transaction($payment_token){
    try{
      // verify using the library
      $transaction  = \Paystack\Transaction::verify($payment_token);
    } catch(Exception $e){
      $transaction = false;
      error_log('Paystack Error: '. $e->getMessage());
      // print_r($e->getResponseObject());
      // return ($e->getMessage());
    }
    return $transaction;
  }

  public static function zero_decimal_currencies_list(){
    return array();
  }

  public static function convert_charge_amount_to_requirements($charge_amount){
    $iso_code = self::get_currency_iso_code();
    if(in_array($iso_code, self::zero_decimal_currencies_list())){
      return round($charge_amount);
    }else{
      return $charge_amount * 100;
    }
  }

  public static function load_countries_list(){
  	$country_codes = ["CI" => "Côte d'Iviore",
                      "NG" => "Nigeria",
                      "ZA" => "South Africa"];
  	return $country_codes;
  }

  public static function load_currencies_list(){
    return ["XOF" => "FCFA",
            "USD" => "US Dollar",
            "NGN" => "Nigerian naira",
            "ZAR" => "South African rand"];
  }

}