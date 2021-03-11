<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PR_DHL_WC_Order_Freight' ) ) :

    class PR_DHL_WC_Order_Freight extends PR_DHL_WC_Order {

        protected $carrier = 'DHL Freight';

        protected $service = 'DHL Freight';

        private $additional_services_whitelist = [
            'greenFreight', 'insurance', 'dangerousGoodsLimitedQuantity'
        ];

        private $additional_services = [];

        private $transportation;

        
        public function init_hooks(){

            parent::init_hooks();

            add_filter( 'pr_shipping_dhl_label_args', array( $this, 'checkRules' ), 10, 2 );
        }

        protected function get_default_dhl_product($order_id)
        {
            return [];
        }

        protected function get_tracking_url() {
            return 'https://activetracing.dhl.com/DatPublic/search.do?search=consignmentId&autoSearch=true&l=sv&at=consignment&a=';
        }

        public function get_bulk_actions() {
            $shop_manager_actions = array(
                // 'pr_dhl_create_labels'      => __( 'DHL Create Labels', 'pr-shipping-dhl' )
            );

            return $shop_manager_actions;
        }

        public function get_dhl_label_items( $order_id )
        {
            $items = parent::get_dhl_label_items($order_id);

            $order = wc_get_order($order_id);

            if (! $this->additional_services) {
                $this->setAdditionalServices();
            }

            if (! is_array($items)) {
                $items = [];
            }

            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                if (! isset($items[$field_name])) {
                    $items[$field_name] = false;
                }

                if ($field_name === 'pr_dhl_insurance') {
                    $insurance_amount_field_name = sprintf('%s_amount', $field_name);

                    if (! isset($items[$insurance_amount_field_name])) {
                        $items[$insurance_amount_field_name] = $order->get_total();
                    }
                }
            }

            return $items;
        }

        public function additional_meta_box_fields($order_id, $is_disabled, $dhl_label_items, $dhl_obj)
        {
            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_width',
                'label'       		=> __( 'Package Width (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_width'] ) ?
                        $dhl_label_items['pr_dhl_package_width'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_width']) ? $this->shipping_dhl_settings['pr_dhl_package_width'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_height',
                'label'       		=> __( 'Package Height (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_height'] ) ?
                        $dhl_label_items['pr_dhl_package_height'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_height']) ? $this->shipping_dhl_settings['pr_dhl_package_height'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            woocommerce_wp_text_input([
                'id'          		=> 'pr_dhl_package_length',
                'label'       		=> __( 'Package Length (cm):', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=>
                    isset( $dhl_label_items['pr_dhl_package_length'] ) ?
                        $dhl_label_items['pr_dhl_package_length'] :
                        (isset($this->shipping_dhl_settings['pr_dhl_package_length']) ? $this->shipping_dhl_settings['pr_dhl_package_length'] : 0),
                'custom_attributes'	=> array( $is_disabled => $is_disabled )
            ]);

            foreach ($this->additional_services as $additional_service)
            {
                $field_name = sprintf('pr_dhl_%s', $additional_service->type);

                woocommerce_wp_checkbox([
                    'id'          		=> $field_name,
                    'label'       		=> __( $additional_service->name, 'pr-shipping-dhl' ),
                    'placeholder' 		=> '',
                    'description'		=> '',
                    'value'       		=> isset( $dhl_label_items[$field_name] ) ? $dhl_label_items[$field_name] : $this->shipping_dhl_settings[$field_name],
                    'custom_attributes'	=> array( $is_disabled => $is_disabled )
                ]);

                if ($field_name === 'pr_dhl_insurance') {

                    $insurance_amount_field_name = sprintf('%s_amount', $field_name);

                    woocommerce_wp_text_input([
                        'id'          		=> $insurance_amount_field_name,
                        'label'       		=> __( 'Insurance Amount:', 'pr-shipping-dhl' ),
                        'placeholder' 		=> '',
                        'description'		=> '',
                        'value'       		=>
                            isset( $dhl_label_items[$insurance_amount_field_name] ) ?
                                $dhl_label_items[$insurance_amount_field_name] :
                                $this->shipping_dhl_settings[$insurance_amount_field_name],
                        'custom_attributes'	=> array( $is_disabled => $is_disabled )
                    ]);
                }
			}
						
			woocommerce_wp_checkbox([
				'id'          		=> 'pr_dhl_label_return',
				'label'       		=> __( 'Return Label:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_label_return'] ) ? $dhl_label_items['pr_dhl_label_return'] : null,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			]);

            if( isset( $this->shipping_dhl_settings['dhl_enable_pickup'] ) && ( $this->shipping_dhl_settings['dhl_enable_pickup'] == 'yes') ) {

                woocommerce_wp_text_input([
                                'id'                => 'pr_dhl_pickup_date',
                                'label'             => __( 'Pickup Date:', 'pr-shipping-dhl' ),
                                'placeholder'       => '',
                                'description'       => '',
                                'value'             =>
                                    isset( $dhl_label_items['pr_dhl_pickup_date'] ) ?
                                        $dhl_label_items['pr_dhl_pickup_date'] :
                                        (isset($this->shipping_dhl_settings['pr_dhl_pickup_date']) ? $this->shipping_dhl_settings['pr_dhl_pickup_date'] : null),
                                'custom_attributes' => array( $is_disabled => $is_disabled ),
                                'class'             => 'short date-picker'
                            ]);
            }
            

            // Enqueue scripts in the way the parent did
            // wp_enqueue_script( 'pr-dhl-fr-main-script-admin', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl-admin.js', array(), PR_DHL_VERSION );
            // wp_enqueue_style( 'pr-dhl-fr-main-style-admin', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl-admin.css');
        }

        public function get_additional_meta_ids()
        {
            return array( 'pr_dhl_insurance_amount', 'pr_dhl_package_width', 'pr_dhl_package_length', 'pr_dhl_package_height', 'pr_dhl_label_return', 'pr_dhl_pickup_date','pr_dhl_greenFreight', 'pr_dhl_insurance', 'pr_dhl_dangerousGoodsLimitedQuantity' );
        }

        protected function get_label_args_settings($order_id, $dhl_label_items)
        {
            // Get services etc.
            $meta_box_ids = $this->get_additional_meta_ids();
            
            foreach ($meta_box_ids as $value) {
                $api_key = str_replace('pr_dhl_', '', $value);

                if ( isset( $dhl_label_items[ $value ] ) ) {
                    $args['order_details'][ $api_key ] = $dhl_label_items[ $value ];
                }
            }

            // Cast access point info to array to be used in Item_Info accordingly
            $args['access_point'] = (array) get_post_meta($order_id, 'dhl_freight_point', true);

            $args['dhl_settings']['account_name'] = $this->shipping_dhl_settings['dhl_client_name'];
            $args['dhl_settings']['store_address'] = get_option( 'woocommerce_store_address' );
            $args['dhl_settings']['store_city'] = get_option( 'woocommerce_store_city' );
            $args['dhl_settings']['store_postcode'] = get_option( 'woocommerce_store_postcode' );
            $args['dhl_settings']['store_country'] = PR_DHL()->get_base_country();
            $args['dhl_settings']['account_num'] = $this->shipping_dhl_settings['dhl_client_account']; //'350009';
            $args['dhl_settings']['api_key'] = $this->shipping_dhl_settings['dhl_client_key'];
            $args['dhl_settings']['enable_pickup'] = $this->shipping_dhl_settings['dhl_enable_pickup'];
            
            return $args;
        }

        public function checkRules($params, $order_id )
        {
            // Check if access point set
            if (! get_post_meta($order_id, 'dhl_freight_point', true)) {
                throw new Exception(__('Invalid access point!', 'pr-shipping-dhl'));
            }

            // Check if products info set
            $serviceData = get_post_meta($order_id, 'dhl_freight_additional_services', true);

            if (! $serviceData) {
                throw new Exception(__('Invalid service information!', 'pr-shipping-dhl'));
            }

            $currency = get_woocommerce_currency();

            if (
                ! $currency ||
                $currency !== $this->getAllowedCurrency()
            ) {
                throw new \Exception('Invalid shop currency!');
            }

            // Check if weight set and is good
            if (! isset($params['order_details']['weight']) ||
                ! $params['order_details']['weight'] ||
                $params['order_details']['weight'] < $serviceData->piece->actualWeightMin ||
                $params['order_details']['weight'] > $serviceData->piece->actualWeightMax
            ) {
                throw new \Exception('Invalid package weight!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_width']) ||
                ! $params['order_details']['package_width'] ||
                $params['order_details']['package_width'] < $serviceData->piece->widthMin ||
                $params['order_details']['package_width'] > $serviceData->piece->widthMax
            ) {
                throw new \Exception('Invalid package width!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_height']) ||
                ! $params['order_details']['package_height'] ||
                $params['order_details']['package_height'] < $serviceData->piece->heightMin ||
                $params['order_details']['package_height'] > $serviceData->piece->heightMax
            ) {
                throw new \Exception('Invalid package height!');
            }

            // Check if length set and is good
            if (! isset($params['order_details']['package_length']) ||
                ! $params['order_details']['package_length'] ||
                $params['order_details']['package_length'] < $serviceData->piece->lengthMin ||
                $params['order_details']['package_length'] > $serviceData->piece->lengthMax
            ) {
                throw new \Exception('Invalid package length!');
            }

            // Check insurance
            if (
                isset($params['order_details']['insurance']) &&
                $params['order_details']['insurance'] === 'yes' &&
                (
                    ! isset($params['order_details']['insurance_amount']) ||
                    $params['order_details']['insurance_amount'] > $serviceData->highValueLimit
                )
            ) {
                throw new \Exception('Invalid insurance amount!');
            }

            // // Check Pickupdate
            // if (! isset($params['order_details']['pickup_date']) ||
            //     ! $params['order_details']['pickup_date']
            // ) {
            //     throw new \Exception('Invalid pickup date!');
            // }

            return $params;
        }

        protected function get_tracking_note( $order_id ) {
            $tracking_note = '';
            $label_tracking_info = $this->get_dhl_label_tracking( $order_id );

            if( ! empty( $label_tracking_info['pickup_response']->message ) ) {

                if (!empty( $label_tracking_info['pickup_response']->message ) ) {
                    $tracking_note = '<br/><p>' . $label_tracking_info['pickup_response']->message . '</p>';
                }
            }

            $tracking_note .= parent::get_tracking_note( $order_id );
            
            return $tracking_note;
        }

        

        private function getAdditionalServicesWhiteList()
        {
            return apply_filters('pr_dhl_freight_additional_services_allowed', $this->additional_services_whitelist);
        }

        private function setAdditionalServices()
        {
            global $post;

            $post_id = $post ? $post : (isset($_POST['order_id']) && $_POST['order_id'] ? $_POST['order_id'] : null );

            if (! $post_id) {
                return;
            }

			$order = wc_get_order($post_id);
			
			$freight_addi_sevices = $order->get_meta('dhl_freight_additional_services', true);
			
			/*$this->additional_services = $freight_addi_sevices->additionalServices->filter(function ($item) {
				return in_array($item->type, $this->getAdditionalServicesWhiteList());
			});*/

			if( isset( $freight_addi_sevices->additionalServices ) ){

				foreach( $freight_addi_sevices->additionalServices as $addi_service ){
					if( isset( $addi_service->type ) && in_array( $addi_service->type, $this->getAdditionalServicesWhiteList() ) ){

						$this->additional_services[] = $addi_service;	

					}
				}
			}
        }

        private function getAllowedCurrency()
        {
            return 'SEK';
        }

        public function process_download_awb_label() {
            global $wp_query;

            $dhl_order_id = isset($wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ] )
                ? $wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ]
                : null;

            if (! $dhl_order_id) {
                return;
            }

            $label_info = get_post_meta($dhl_order_id, 'dhl_freight_print_document_data', true);

            header("Content-type:application/pdf");
            header(sprintf("Content-Disposition:attachment; filename=%s", $label_info[0]->name));

            echo base64_decode($label_info[0]->content);
        }

        protected function can_delete_label($order_id) {
            return false;
        }

        protected function get_delete_label_msg() {
            return '<p class="wc_dhl_delete_msg">' . __('To cancel this booking you must call DHL Freight customer support at 0771-345-345.', 'pr-shipping-dhl') . '</p>';
        }
    }

endif;
