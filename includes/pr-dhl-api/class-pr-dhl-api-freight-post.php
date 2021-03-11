<?php

// Exit if accessed directly or class already exists
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Freight\Auth;
use PR\DHL\REST_API\Freight\Client;
use PR\DHL\REST_API\Freight\Item_Info;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Freight_Post', false ) ) {
    return;
}

class PR_DHL_API_Freight_Post extends PR_DHL_API
{
    const API_URL_PRODUCTION = 'https://api.freight-logistics.dhl.com/';

    const API_URL_SANDBOX = 'https://test-api.freight-logistics.dhl.com/';

    const FREIGHT_PRODUCT_CODE_RETURN = '104';

    /**
     * The API driver instance.
     *
     * @since [*next-version*]
     *
     * @var API_Driver_Interface
     */
    public $api_driver;
    /**
     * The API authorization instance.
     *
     * @since [*next-version*]
     *
     * @var Auth
     */
    public $api_auth;
    /**
     * The API client instance.
     *
     * @since [*next-version*]
     *
     * @var Client
     */
    public $api_client;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string $country_code The country code.
     *
     * @throws Exception If an error occurred while creating the API driver, auth or client.
     */
    public function __construct( $country_code ) {
        $this->country_code = $country_code;

        try {
            $this->api_driver = $this->create_api_driver();
            $this->api_auth = $this->create_api_auth();
            $this->api_client = $this->create_api_client();
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    public function is_dhl_freight() {
        return true;
    }

    public function get_settings()
    {
        return get_option('woocommerce_pr_dhl_fr_settings', []);
    }

    public function get_dhl_products_international()
    {
        return [];
    }

    public function get_dhl_products_domestic()
    {
        return [
            103 => __('Service Point')
        ];
    }

    /**
     * Initializes the API client instance.
     *
     * @return Client
     *
     * @throws Exception If failed to create the API client.
     */
    protected function create_api_client() {
        // Create the API client, using this instance's driver and auth objects
        return new Client(
            $this->get_api_url(),
            $this->api_driver,
            $this->api_auth
        );
    }

    /**
     * Initializes the API driver instance.
     *
     * @since [*next-version*]
     *
     * @return API_Driver_Interface
     *
     * @throws Exception If failed to create the API driver.
     */
    protected function create_api_driver() {
        // Use a standard WordPress-driven API driver to send requests using WordPress' functions
        $driver = new WP_API_Driver();

        // This will log requests given to the original driver and log responses returned from it
        $driver = new Logging_Driver( PR_DHL(), $driver );

        // This will prepare requests given to the previous driver for JSON content
        // and parse responses returned from it as JSON.
        $driver = new JSON_API_Driver( $driver );

        //, decorated using the JSON driver decorator class
        return $driver;
    }

    /**
     * Initializes the API auth instance.
     *
     * @throws Exception If failed to create the API auth.
     */
    protected function create_api_auth() {
        return new Auth(
            $this->get_client_key()
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function get_dhl_label( $args ) {
        // error_log(print_r($args,true));
        $order_id = isset( $args[ 'order_details' ][ 'order_id' ] )
            ? $args[ 'order_details' ][ 'order_id' ]
            : null;

        $uom                = get_option( 'woocommerce_weight_unit' );
        // $label_format       = $args['dhl_settings']['label_format'];
        $is_cross_border    = PR_DHL()->is_crossborder_shipment( $args['shipping_address']['country'] );
        try {
            $item_info = new Item_Info( $args, $uom, $is_cross_border );
        } catch (Exception $e) {
            throw $e;
        }

        $transport_response = $this->api_client->transportation_request( $item_info );

        $pickup_reponse = '';
        if ( $this->get_setting('dhl_enable_pickup') == 'yes') {

            if( $this->api_client->validate_postal_code( $item_info ) ) {
                $pickup_reponse = $this->api_client->pickup_request( $transport_response );

            } else {
                throw new \Exception(__( 'Postcode is not valid for pickup', 'pr-shipping-dhl' ) );
            }
        }

        $label_response = $this->api_client->print_documents_request( $transport_response );
        $label_path = $this->get_label_path( $label_response );

        if( $item_info->shipment['is_return'] ) {
			$label_path_return 	= $this->get_dhl_label_return( $item_info );

			$label_path 		= $this->merge_dhl_label_output( array(
				$label_path,
				$label_path_return
			), $order_id );
        }

        return array(
            'label_path'            => $label_path,
            'tracking_number'       => $transport_response->id,
            'routing_code'          => $transport_response->routingCode,
            'pickup_response'       => $pickup_reponse
        );
	}
	
	protected function merge_dhl_label_output( $label_paths, $order_id ){
		if( count( $label_paths) == 1 ){
			foreach( $label_paths as $path ){
				if( file_exists( $path ) ){
					return $path;
				}
			}
		}
		
		// Merge PDF files
		$loader = PR_DHL_Libraryloader::instance();
		$pdfMerger = $loader->get_pdf_merger();

		if( $pdfMerger ){
			
			$has_file = false;
			
			foreach( $label_paths as $path ){
				if( file_exists( $path ) ){
					$pdfMerger->addPDF( $path, 'all' );
					$has_file = true;
				}
			}

			if( $has_file ){
                $filename = 'dhl-label-return-' . $order_id . '.pdf';
				$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
				$pdfMerger->merge( 'file',  $label_path );

				return $label_path;
			}
			
		}

		return false;
	}

    protected function get_dhl_label_return( $item_info ) {

        $item_info->shipment['product'] = static::FREIGHT_PRODUCT_CODE_RETURN;

        $transport_response = $this->api_client->transportation_request( $item_info, true );

        $label_response = $this->api_client->print_documents_request( $transport_response );

        return $this->get_label_path( $label_response, 'return' );
    }

    protected function get_label_path( $label_response, $type = 'item' ) {

        if ( !empty( $label_response[0]->valid ) ) {
            // $label_pdf_data  = base64_decode( $label_info->content );
            // $shipment_id        = $label_info->shipmentID;
            $this->save_dhl_label_file( $type, $label_response[0]->name, base64_decode( $label_response[0]->content ) );

            return $this->get_dhl_label_file_info( $type, $label_response[0]->name )->path;
        }

        throw new \Exception(__( 'No label was created.', 'pr-shipping-dhl' ) );
    }

    /**
     * Get Freight service points
     */
    public function get_dhl_freight_service_points($args) {
        $defaultAddress = [
            'street' => '',
            'cityName' => '',
            'postalCode' => '',
            'countryCode' => 'SE',
        ];

        $params = [
            'address' => array_merge($defaultAddress, $args)
        ];

        return $this->api_client->get_service_points($params);
    }

    /**
     * Get Freight Additional Services
     *
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function get_dhl_freight_products($args = []) {
        $defaultParams = [
            'toCountryCode' => 'SE'
        ];

        $params = array_merge($defaultParams, $args);

        return $this->api_client->get_products(103, $params);
    }

    /**
     * @param array $args
     * @return stdClass|string
     * @throws Exception
     */
    public function dhl_valid_postal_code($args = []) {
        $default = [
            'countryCode' => 'SE',
            'postalCode' => '',
            'city' => '',
        ];

        $params = array_merge($default, $args);

        return $this->api_client->validate_postal_code($params);
    }


    /**
     * Retrieves the filename for DHL item label files.
     *
     * @since [*next-version*]
     *
     * @param string $barcode The DHL item barcode.
     * @param string $format The file format.
     *
     * @return string
     */
    public function get_dhl_item_label_file_name( $barcode, $format = 'pdf' ) {
        return sprintf('dhl_%s', $barcode);
    }

    /**
     * Retrieves the filename for DHL order label files (a.k.a. merged return label files).
     *
     * @since [*next-version*]
     *
     * @param string $order_id The DHL order ID.
     * @param string $format The file format.
     *
     * @return string
     */
    public function get_dhl_return_label_file_name( $barcode, $format = 'pdf' ) {
        return sprintf('dhl_return_%s', $barcode);
    }

    /**
     * Retrieves the file info for DHL return label files.
     *
     * @since [*next-version*]
     *
     * @param string $awb The AWB.
     * @param string $format The file format.
     *
     * @return object An object containing the file "path" and "url" strings.
     */
    public function get_dhl_return_label_file_info( $barcode, $format = 'pdf' ) {
        $file_name = $this->get_dhl_return_label_file_name($barcode, $format);

        return (object) array(
            'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
            'url' => PR_DHL()->get_dhl_label_folder_url() . $file_name,
        );
    }

    /**
     * Retrieves the file info for any DHL label file, based on type.
     *
     * @since [*next-version*]
     *
     * @param string $type The label type: "item" or "order".
     * @param string $key The key: barcode for type "item", and order ID for type "order".
     *
     * @return object An object containing the file "path" and "url" strings.
     */
    public function get_dhl_label_file_info( $type, $key ) { 

        // Return file info for "awb" type
        if ( $type === 'return') {
            return $this->get_dhl_return_label_file_info( $key );
        }

        // Return info for "item" type
        return $this->get_dhl_item_label_file_info( $key, 'pdf' );
    }

    /**
     * Retrieves the API URL.
     *
     * @since [*next-version*]
     *
     * @return string
     *
     * @throws Exception If failed to determine if using the sandbox API or not.
     */
    public function get_api_url() {
        $is_sandbox = $this->get_setting( 'dhl_sandbox' );
        $is_sandbox = filter_var($is_sandbox, FILTER_VALIDATE_BOOLEAN);
        $api_url = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

        return $api_url;
    }

    /**
     * Retrieves the API credentials.
     *
     * @return string The rest api client key
     *
     * @throws Exception If failed to retrieve the API credentials.
     */
    public function get_client_key() {
        return $this->get_setting( 'dhl_client_key' );
    }

    /**
     * Retrieves a single setting.
     *
     * @param string $key     The key of the setting to retrieve.
     * @param string $default The value to return if the setting is not saved.
     *
     * @return mixed The setting value.
     */
    public function get_setting( $key, $default = '' ) {
        $settings = $this->get_settings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function delete_dhl_label( $label_info ) {
        if ( ! isset( $label_info['label_path'] ) ) {
            throw new Exception( __( 'DHL Label has no path!', 'pr-shipping-dhl' ) );
        }

        $label_path = $label_info['label_path'];

        if ( file_exists( $label_path ) ) {
            $res = unlink( $label_path );

            if ( ! $res ) {
                throw new Exception( __( 'DHL Label could not be deleted!', 'pr-shipping-dhl' ) );
            }
        }
    }
}
