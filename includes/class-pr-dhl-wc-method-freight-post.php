<?php

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_WC_Method_Freight_Post', false ) ) {
	return;
}

class PR_DHL_WC_Method_Freight_Post extends WC_Shipping_Method {
    /**
     * Init and hook in the integration.
     */
    public function __construct( $instance_id = 0 ) {
        $this->id = 'pr_dhl_fr';
        $this->instance_id = absint( $instance_id );
        $this->title = __( 'DHL Freight (Sweden)', 'pr-shipping-dhl' );
        $this->method_title = __( 'DHL Freight (Sweden)', 'pr-shipping-dhl' );
        $this->method_description =  __( 'To start using this plugin for creating pickup request, send shipment data and print necessary documents for DHL Freight (Sweden) and the product DHL Service Point and DHL Service Point Return. ', 'pr-shipping-dhl' );

        $this->init();
    }

    /**
     * Initializes the instance.
     *
     * @since [*next-version*]
     */
    protected function init() {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Get message
     *
     * @return string Error
     */
    private function get_message( $message, $type = 'notice notice-error is-dismissible' ) {

        ob_start();
        ?>
        <div class="<?php echo $type ?>">
            <p><?php echo $message ?></p>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Initialize integration settings form fields.
     *
     * @throws Exception
     */
    public function init_form_fields() {

        $weight_units = get_option( 'woocommerce_weight_unit' );
        
        $log_path = PR_DHL()->get_log_url();

        $this->form_fields = array(
            'dhl_api'                    => array(
                'title'       => __( 'Account and API Settings', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __(
                    'Please configure your account and API settings.',
                    'pr-shipping-dhl'
                ),
                'class'       => '',
            ),
            'dhl_client_name'                => array(
                'title'       => __( 'Customer Name', 'pr-shipping-dhl' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'dhl_client_account'  => array(
                'title'       => __( 'Customer Account', 'pr-shipping-dhl' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'dhl_client_key'                => array(
                'title'       => __( 'Application Key', 'pr-shipping-dhl' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'dhl_sandbox'                => array(
                'title'       => __( 'Sandbox Mode', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Sandbox Mode', 'pr-shipping-dhl' ),
                'default'     => 'no',
                'description' => __(
                    'Please, tick here if you want to test the plug-in installation against the DHL Freight (Sweden) sandbox environment. Labels generated via Sandbox cannot be used for shipping.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
            ),
            'dhl_debug'                  => array(
                'title'       => __( 'Debug Log', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable logging', 'pr-shipping-dhl' ),
                'default'     => 'yes',
                'description' => sprintf(
                    __(
                        'A log file containing the communication to the DHL Freight server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.',
                        'pr-shipping-dhl'
                    ),
                    '<a href="' . $log_path . '" target = "_blank">',
                    '</a>'
                ),
            ),
            'dhl_pickup_dist'  => array(
                'title'       => __( 'Shipping', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
                'class'       => '',
            ),
            'dhl_enable_pickup' => array(
                'title'       => __( 'Enable Pickup', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'description' => __(
                    'This should be ticked if not regular pickup is agreed with DHL. This service means that a message will be send to DHL that a parcel is ready for pickup.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'dhl_add_weight_type'   => array(
                'title'       => __( 'Additional Weight Type', 'pr-shipping-dhl' ),
                'type'        => 'select',
                'description' => __(
                    'Select whether to add an absolute weight amount or percentage amount to the total product weight.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'options'     => array( 'absolute' => 'Absolute', 'percentage' => 'Percentage' ),
                'class'       => 'wc-enhanced-select',
            ),
            'dhl_add_weight'        => array(
                'title'       => sprintf( __( 'Additional Weight (%s or %%)', 'pr-shipping-dhl' ), $weight_units ),
                'type'        => 'text',
                'description' => __(
                    'Add extra weight in addition to the products.  Either an absolute amount or percentage (e.g. 10 for 10%).',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'default'     => '',
                'placeholder' => '',
                'class'       => 'wc_input_decimal',
            ),
            'dhl_tracking_note'     => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Make Private', 'pr-shipping-dhl' ),
                'default'     => 'no',
                'description' => __(
                    'Please, tick here to not send an email to the customer when the tracking number is added to the order.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
            ),
            'dhl_tracking_note_txt' => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'textarea',
                'description' => __(
                    'Set the custom text when adding the tracking number to the order notes. {tracking-link} is where the tracking number will be set.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => false,
                'default'     => __( 'DHL Parcel Sweden Tracking Number: {tracking-link}.', 'pr-shipping-dhl' ),
            ),
            'dhl_service_points'                    => array(
                'title'       => __( 'Service Points Settings', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'class'       => '',
            ),
            'dhl_display_google_maps' => array(
                'title'             => __( 'Google Maps', 'pr-shipping-dhl' ),
                'type'              => 'checkbox',
                'label'             => __( 'Enable Google Maps', 'pr-shipping-dhl' ),
                'default'           => 'yes',
                'description'       => __( 'Enabling this will display Google Maps on the front-end.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
            ),
            'dhl_google_maps_api_key' => array(
                'title'             => __( 'API Key', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => sprintf( __( 'The Google Maps API Key is necassary to display the DHL Locations on a google map.<br/>Get a free Google Maps API key %shere%s.', 'pr-shipping-dhl' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target = "_blank">', '</a>' ),
                'desc_tip'          => false,
                'class'             => ''
            ),
        );
    }

    /**
     * Generate Button HTML.
     *
     * @access public
     *
     * @param mixed $key
     * @param mixed $data
     *
     * @since  1.0.0
     * @return string
     */
    public function generate_button_html( $key, $data ) {
        $field = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => array(),
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
                    </legend>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button"
                            name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>"
                            style="<?php echo esc_attr(
                                $data['css']
                            ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post(
                            $data['title']
                        ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
	}

    /**
     * Processes and saves options.
     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
     */
    public function process_admin_options() {

        try {
            $dhl_obj = PR_DHL()->get_dhl_factory();
			$dhl_obj->dhl_reset_connection();
        } catch ( Exception $e ) {

            echo $this->get_message( __( 'Could not reset connection: ', 'pr-shipping-dhl' ) . $e->getMessage() );
            // throw $e;
        }

        return parent::process_admin_options();
    }
}
