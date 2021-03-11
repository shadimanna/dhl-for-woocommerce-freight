<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

abstract class PR_DHL_API {

	protected $dhl_label = null;
	protected $dhl_finder = null;

	protected $country_code;

	// abstract public function set_dhl_auth( $client_id, $client_secret );
	
	public function is_dhl_paket( ) {
		return false;
	}

	public function is_dhl_ecs( ) {
		return false;
	}

	public function is_dhl_ecs_asia( ) {
		return false;
	}

	public function is_dhl_ecomm( ) {
		return false;
	}

	public function is_dhl_deutsche_post( ) {
		return false;
	}

	public function is_dhl_freight() {
	    return false;
    }

	public function get_dhl_label( $args ) {
		return $this->dhl_label->get_dhl_label( $args );
	}

	public function delete_dhl_label( $label_url ) {
		return $this->dhl_label->delete_dhl_label( $label_url );
	}

	public function get_parcel_location( $args ) {
		if ( $this->dhl_finder ) {
			return $this->dhl_finder->get_parcel_location( $args );
		} else {
			throw new Exception( __('Parcel Finder not available', 'pr-shipping-dhl') );
		}
	}

	abstract public function get_dhl_products_international();

	abstract public function get_dhl_products_domestic();

	public function get_dhl_content_indicator( ) {
		return array();
	}

	public function dhl_test_connection( $client_id, $client_secret ) {
		return $this->dhl_label->dhl_test_connection( $client_id, $client_secret );
	}

	public function dhl_validate_field( $key, $value ) {
		return $this->dhl_label->dhl_validate_field( $key, $value );
	}

	public function dhl_reset_connection( ) {
		return;
	}

	public function get_dhl_preferred_day_time( $postcode, $account_num, $cutoff_time, $working_days ) {
		return array();
	}

	public function get_dhl_duties() {
		$duties = array(
					'DDU' => __('Delivery Duty Unpaid', 'pr-shipping-dhl'),
					'DDP' => __('Delivery Duty Paid', 'pr-shipping-dhl')
					);
		return $duties;
	}

	public function get_dhl_visual_age() {
		return array();	
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
		return sprintf('dhl-label-%s.%s', $barcode, $format);
	}

	/**
	 * Retrieves the file info for a DHL item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The DHL item barcode.
	 * @param string $format The file format.
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_item_label_file_info( $barcode, $format = 'pdf' ) {
		$file_name = $this->get_dhl_item_label_file_name($barcode, $format);

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	abstract public function get_dhl_label_file_info( $type, $key );

	/**
	 * Saves an item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", or "order".
	 * @param string $key The key: barcode for type "item", and order ID for type "order".
	 * @param string $data The label file data.
	 *
	 * @return object The info for the saved label file, containing the "path" and "url".
	 *
	 * @throws Exception If failed to save the label file.
	 */
	public function save_dhl_label_file( $type, $key, $data ) {
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		if ( validate_file( $file_info->path ) > 0 ) {
			throw new Exception( __( 'Invalid file path!', 'pr-shipping-dhl' ) );
		}

		$file_ret = file_put_contents( $file_info->path, $data );

		if ( empty( $file_ret ) ) {
			throw new Exception( __( 'DHL label file cannot be saved!', 'pr-shipping-dhl' ) );
		}

		return $file_info;
	}

	/**
	 * Deletes an AWB label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", "awb" or "order".
	 * @param string $key The key: barcode for type "item", AWB for type "awb" and order ID for type "order".
	 *
	 * @throws Exception If the file could not be deleted.
	 */
	public function delete_dhl_label_file( $type, $key )
	{
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		// Do nothing if file does not exist
		if ( ! file_exists( $file_info->path ) ) {
			return;
		}

		// Attempt to delete the file
		$res = unlink( $file_info->path );

		// Throw error if the file could not be deleted
		if (!$res) {
			throw new Exception(__('Label could not be deleted!', 'pr-shipping-dhl'));
		}
	}
}
