<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PR_DHL_Front_End_Freight' ) ) :

    class PR_DHL_Front_End_Freight {

        private $dhl;

        /**
         * Init and hook in the integration.
         */
        public function __construct()
        {
            $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

            $this->init_hooks();
        }

        public function init_hooks() {
            // Assets
            add_action( 'wp_enqueue_scripts', [$this, 'loadStylesScripts']);

            // Service Point routes
            add_action( 'wp_ajax_dhl_service_point_search', [$this, 'lookForServicePoints']);
            add_action( 'wp_ajax_nopriv_dhl_service_point_search', [$this, 'lookForServicePoints']);

            // Markup
            add_action( 'woocommerce_before_checkout_shipping_form', [$this, 'addFreightForm']); // Form
            add_action( 'woocommerce_after_checkout_form', [$this, 'addMapPopUp']); // Popup

            // Validate data
            add_action( 'woocommerce_checkout_process', [$this, 'validate']);

            // Save freight data
            add_action( 'woocommerce_checkout_create_order', [$this, 'saveFreightFields']);

            // Save freight additional services
            add_action('woocommerce_checkout_order_processed', [$this, 'saveFreightServices'], 10, 3);
        }

        /**
         * Update order with freight point
         *
         * @param WC_Order $order
         */
        public function saveFreightFields(WC_Order $order)
        {
            if (! $this->isFreightSelected()) {
                return;
            }

            $point_data = wc_clean($_POST['dhl_freight_selected_service_point']);

            if (! $point_data) {
                return;
            }

            $order->update_meta_data(
                'dhl_freight_point',
                json_decode(
                    stripslashes(
                        $point_data
                    )
                )
            );
        }

        /**
         * Save Freight additional services for that order
         *
         * @param $order_id
         * @param $data
         * @param WC_Order $order
         */
        public function saveFreightServices($order_id, $data, WC_Order $order)
        {
            // Try API
            try {
                $dhl_obj = PR_DHL()->get_dhl_factory();

                $data = $dhl_obj->get_dhl_freight_products();

                update_post_meta($order_id, 'dhl_freight_additional_services',  $data);

            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        /**
         * Checkout form freight validation
         */
        public function validate()
        {
            // Validate input only if "ship to different address" flag is set
            if ( ! $this->isFreightSelected()) {
                return;
            }

            $billing_country = wc_clean( $_POST['billing_country'] );
            $prefered_countries = [wc_get_base_location()['country']];

            if (! in_array($billing_country, $prefered_countries)) {
                wc_add_notice( __( 'DHL Freight is not available in selected country!', 'dhl' ), 'error' );
                return;
            }

        }

        /**
         * Add Popup with map
         */
        public function addMapPopUp()
        {
            if (! $this->isGoogleMapEnabled()) {
                return;
            }

            wc_get_template('checkout/dhl-freight-finder.php', [], '', PR_DHL_PLUGIN_DIR_PATH . '/templates/');
        }

        /**
         * Add map finder button
         */
        public function addFreightForm()
        {
            wc_get_template('checkout/dhl-freight-fields.php', [
                'isGoogleMapAvailable' => $this->isGoogleMapEnabled()
            ], '', PR_DHL_PLUGIN_DIR_PATH . '/templates/');
        }

        /**
         * Check if freight point selected
         *
         * @return bool
         */
        private function isFreightSelected()
        {
            return isset($_POST['ship_to_different_address']) &&
                isset($_POST['dhl_freight_selected_service_point']) &&
                $_POST['dhl_freight_selected_service_point'];
        }

        /**
         * Check if google map enabled
         *
         * @return bool
         */
        public function isGoogleMapEnabled()
        {
            return
                $this->shipping_dhl_settings['dhl_display_google_maps'] === 'yes'
                && $this->shipping_dhl_settings['dhl_google_maps_api_key'];
        }

        /**
         * Load assets
         */
        public function loadStylesScripts()
        {
            wp_enqueue_script('pr-dhl-fr-main-script', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl.js', ['jquery']);
            wp_localize_script('pr-dhl-fr-main-script', 'dhl', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce("dhl_freight"),
                'shopCountry' => wc_get_base_location()
            ]);

            wp_enqueue_style( 'pr-dhl-fr-main-style', PR_DHL_PLUGIN_DIR_URL . '/assets/dist/dhl.css');

            // Google MAP API Key registration
            if ($this->isGoogleMapEnabled()) {
                wp_enqueue_script('pr-dhl-fr-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $this->shipping_dhl_settings['dhl_google_maps_api_key']);
            }
        }

        /**
         * Service points API route
         */
        public function lookForServicePoints()
        {
            check_ajax_referer( 'dhl_freight', 'security' );

            $postcode	 = wc_clean( $_POST[ 'dhl_freight_postal_code' ] );
            $city	 	 = wc_clean( $_POST[ 'dhl_freight_city' ] );

            try {
                $dhl_obj = PR_DHL()->get_dhl_factory();

                $args = [
                    'postalCode' => $postcode,
                    'cityName' => $city,
                ];

                $data = $dhl_obj->get_dhl_freight_service_points($args);

                wp_send_json($data);

            } catch (Exception $e) {
                wp_send_json( array( 'error' => $e->getMessage() ) );
            }

            wp_die();
        }
    }

endif;
