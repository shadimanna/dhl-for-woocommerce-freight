<?php

namespace PR\DHL\REST_API\Freight;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {
	/**
	 * The array of shipment information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipment;
	/**
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $access_point;
	/**
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $recipient;
	/**
	 * The array of content item information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $contents;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $weightUom;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $weightUom = 'g' ) {
		$this->weightUom = $weightUom;
		$this->parse_args( $args, $weightUom );
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	protected function parse_args( $args ) {
		$settings = $args[ 'dhl_settings' ];
		$recipient_info = $args[ 'shipping_address' ];
		$access_point = $args[ 'access_point' ];
		$shipping_info = $args[ 'order_details' ];
		$items_info = $args['items'];

		$this->shipment = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->access_point = Args_Parser::parse_args( $access_point, $this->get_access_point_info_schema() );
		$this->shipper = Args_Parser::parse_args( $settings, $this->get_shipper_info_schema() );
		$this->recipient = Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipment_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
            'dhl_product'       => array(
				'rename' => 'product',
				'error'  => __( 'DHL "Product" is empty!', 'pr-shipping-dhl' ),
			),
			'weight'            => array(
				'error'    => __( 'Order "Weight" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $weight ) {
					if ( ! is_numeric( $weight ) ) {
						throw new Exception( __( 'The order "Weight" must be a number', 'pr-shipping-dhl' ) );
					}
				}
			),
			'package_width'          => array(
				'rename' => 'width',
				'error' => __( 'Package width is empty!', 'pr-shipping-dhl' ),
			),
			'package_length'          => array(
				'rename' => 'length',
				'error' => __( 'Package length is empty!', 'pr-shipping-dhl' ),
			),
			'package_height'          => array(
				'rename' => 'height',
				'error' => __( 'Packages height is empty!', 'pr-shipping-dhl' ),
			),
			'currency'          => array(
				'error' => __( 'Shop "Currency" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $value ) {
					if ( $value != 'SEK' ) {
						throw new Exception( __( 'The order "currency" must be "SEK"', 'pr-shipping-dhl' ) );
					}
				},
			),
			'total_value'       => array(
				'rename' => 'value',
				'error'  => __( 'Shipment "Value" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( __( 'The order "value" must be a number', 'pr-shipping-dhl' ) );
					}
				},
				'sanitize' => function( $value ) use ($self) {

					return $self->float_round_sanitization( $value, 2 );
				}
			),
			'pickup_date' => array(

			),
			'insurance_amount' => array(
				'default' => 0,
				'validate' => function( $value, $args ) {
                    if( isset( $args['insurance'] ) && $args['insurance'] == 'yes' && empty( $value ) ) {
                        throw new Exception( __( 'The "Insurance Value" cannot be empty', 'pr-shipping-dhl' ) );
                    }
                },
                'sanitize' => function( $value ) use ($self) {

					return $self->float_round_sanitization( $value, 2 );
				}
			),
			'label_return' => array(
				'default' => false,
				'rename' => 'is_return',
				'sanitize' => function( $value ) use ($self) {

					if( !empty( $value ) && $value == 'yes' ) {
                        return true;
                    } else {
                    	return false;
                    }
				}

			),
			'dangerousGoodsLimitedQuantity' => array(
				'default' => false,
				'sanitize' => function( $value ) use ($self) {

					if( !empty( $value ) && $value == 'yes' ) {
                        return true;
                    } else {
                    	return false;
                    }
				}
			),
			'greenFreight' => array(
				'default' => false,
				'sanitize' => function( $value ) use ($self) {

					if( !empty( $value ) && $value == 'yes' ) {
                        return true;
                    } else {
                    	return false;
                    }
				}
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_recipient_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;
		
		return array(
			'name'      => array(
				'default' => '',
				'error'  => __( 'Recipient is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $name ) use ($self) {

					return $self->string_length_sanitization( $name, 30 );
				}
			),
			'phone'     => array(
				'default' => '',
				'sanitize' => function( $phone ) use ($self) {

					return $self->string_length_sanitization( $phone, 15 );
				}
			),
			'email'     => array(
				'default' => '',
			),
			'address_1' => array(
				'error' => __( 'Shipping "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'address_2' => array(
				'default' => '',
			),
			'city'      => array(
				'error' => __( 'Shipping "City" is empty!', 'pr-shipping-dhl' ),
			),
			'postcode'  => array(
				'default' => '',
			),
			'state'     => array(
				'default' => '',
			),
			'country'   => array(
				'error' => __( 'Shipping "Country" is empty!', 'pr-shipping-dhl' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_access_point_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;
		
		return array(
			'id'   => array(
				'error' => __( 'Access point "id" is empty!', 'pr-shipping-dhl' ),
			),	
			'name'      => array(
				'error' => __( 'Access point "name" is empty!', 'pr-shipping-dhl' ),
			),
			'street' => array(
				'error' => __( 'Access point "street" is empty!', 'pr-shipping-dhl' ),
			),
			'cityName'      => array(
				'rename' => 'city',
				'error' => __( 'Access point "city" is empty!', 'pr-shipping-dhl' ),
			),
			'postalCode'  => array(
				'rename' => 'postcode',
				'error' => __( 'Access point "postcode" is empty!', 'pr-shipping-dhl' ),
			),
			'countryCode'   => array(
				'rename' => 'country',
				'error' => __( 'Access point "country" is empty!', 'pr-shipping-dhl' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipper_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;
		
		return array(
			'account_num'   => array(
				'rename' => 'id',
				'error' => __( 'Account number is empty!', 'pr-shipping-dhl' ),
			),	
			'account_name'      => array(
				'rename' => 'name',
				'error' => __( 'Account name is empty!', 'pr-shipping-dhl' ),
			),
			'store_address' => array(
				'rename' => 'street',
				'error' => __( 'Store address is empty!', 'pr-shipping-dhl' ),
			),
			'store_city'      => array(
				'rename' => 'city',
				'error' => __( 'Store city is empty!', 'pr-shipping-dhl' ),
			),
			'store_postcode'  => array(
				'rename' => 'postcode',
				'error' => __( 'Store postcode is empty!', 'pr-shipping-dhl' ),
			),
			'store_country'   => array(
				'rename' => 'country',
				'error' => __( 'Store country is empty!', 'pr-shipping-dhl' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_content_item_info_schema()
	{
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'     => array(
				'default'  => '',
				'validate' => function( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if (empty($length)) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							__( 'Item HS Code must be between 0 and 20 characters long', 'pr-shipping-dhl' )
						);
					}
				},
			),
			'item_description' => array(
				'rename' => 'description',
				'default' => '',
				'sanitize' => function( $description ) use ($self) {

					return $self->string_length_sanitization( $description, 33 );
				}
			),
			'product_id'  => array(
				'default' => '',
			),
			'sku'         => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename' => 'value',
				'default' => 0,
				'sanitize' => function( $value ) use ($self) {

					return (string) $self->float_round_sanitization( $value, 2 );
				}
			),
			'origin'      => array(
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'         => array(
				'default' => 1,
			),
			'item_weight'      => array(
				'rename' => 'weight'
			),
		);
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( $weight );

		switch ( $uom ) {
			case 'kg':
				return $weight * 1000;

			case 'lb':
				return $weight / 2.2;

			case 'oz':
				return $weight / 35.274;
		}

		return $weight;
	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = round( floatval( $float ), $numcomma);

		// Return float to ensure a string is not passed via the API
        return (float)number_format($float, 2, '.', '');
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if( strlen( $string ) <= $max ){

			return $string;
		}

		return substr( $string, 0, ( $max-1 ));
	}

}
