<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class WC_Print_Api
 */
class WC_Print_Api
{

    /**
     * @var
     */
    private static $instance;

    /**
     * @var
     */
    public $username;

    /**
     * @var
     */
    public $password;

    /**
     * @var
     */
    public $url;

    /**
     * @var false|mixed|void
     */
    public $token;
    /**
     * @var mixed
     */

    public $api_url;



    /**
     * @return WC_Print_Api
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Api constructor.
     */
    public function __construct()
    {
        $options            = get_option('wc_print_settings');
        $this->username     = $options['credentials_username'];
        $this->password     = $options['credentials_password'];
        $this->api_url      = 'https://api.print.com/';
        $this->token_expired();


    }

    public function token_expired(){

        $current_day    =   date('Y-m-d H:i:s');
        $expired_at     =   get_option('_wc_print_bearer_expired_at');
        if( strtotime($current_day) > strtotime($expired_at)){
             $this->login();
            return true;
        }else{
            $this->token  =  get_option('_wc_print_bearer');
            return true;
         }
    }

    public  function login(){

        $credentials['credentials']['username']=$this->username;
        $credentials['credentials']['password']=$this->password;
        $response= $this->call($credentials,'login',false,'POST');
        if(!is_wp_error($response)){
            $code           = wp_remote_retrieve_response_code($response);
            $message        = wp_remote_retrieve_response_message($response);
            $body           = wp_remote_retrieve_body($response);
            $current_day    =  date('Y-m-d H:i:s');
            $expired_at     =  date('Y-m-d H:i:s', strtotime($current_day. ' + 1 days'));
            if($code==200){
                update_option('_wc_print_bearer',json_decode($body));
                update_option('_wc_print_bearer_created_at',$current_day);
                update_option('_wc_print_bearer_expired_at',$expired_at);
                return $this->token=json_decode($body) ;
            }
        }
        return  false;
    }

    public function call($body,$endpoint,$bearer,$method,$put=false)
    {

       $filter_hash = '_wc_print_api_'.md5(
            sprintf(
                '%s-%s-%s',
                $endpoint,
                $method,
                serialize($body)
            )
        );

        $request_cached= get_option( $filter_hash );
        /*
         * if($request_cached){
            return  $request_cached;
        }*/

        $sslverify = false;
        $headers['Content-Type']    =   'application/json';
        $headers['Accept']          =   'application/json';
        $headers['Accept-Language'] =   'fr-FR';
        if($bearer){
            $headers['Authorization'] ='Bearer '.$this->token ;
        }

        $args = array(
            'timeout' => 300,
            'blocking' => true,
            'sslverify' => $sslverify,
            'headers' => $headers,
        );
        if($body){
            $args['body'] =json_encode($body);
        }

        $call_url   =   $this->api_url . $endpoint;
        if($method=='POST'){
            if($put){
                $args['method']='PUT';
            }
            $response   =    wp_remote_post($call_url, $args);
        }

        if($method=='GET'){
            $response   =    wp_remote_get($call_url, $args);
        }


        if(is_wp_error($response)){
            return new WP_Error($response->get_error_code(), $response->get_error_message());
        }
        $code   = wp_remote_retrieve_response_code($response);


        $message = wp_remote_retrieve_response_message($response);
         if($code==403){
           // $this->login();
         }

        $body    = wp_remote_retrieve_body($response);

        if (!in_array($code, array(200, 201))) {
            $body_array = self::format_body($body,$code);
         

            if(!empty($body_array['message'])){
                $message = $body_array['message'];
            }
            if(!empty($body_array['hint'])){
                $message .=  ' : ' .$body_array['hint'];
            }
            if(!empty($body_array['errorMessage'])){
                $message .=  ' ' .$body_array['errorMessage'];
            }

            $message = !empty($message) ? $message : __('Erreur interne du serveur', 'wc-print');
            $message = str_replace('Bad Request','',$message);
            $message = str_replace('[400]','',$message);
            $message = str_replace('Missing required properties','Propriétés requises manquantes',$message);
            return new WP_Error($code, $message);
        }
        //update_option( $filter_hash, $response,'no');

        return $response;

    }

    public static function format_body($body,$code){
        $body_array =  json_decode(($body), true);
        if(empty($body_array)){
            $body = str_replace('['.$code.'] ["','',$body);
            $body = str_replace('"]','',$body);
            $body= str_replace('","',' ',$body);
            $body_array =  json_decode(($body), true);
        }
        return $body_array;
    }
}
