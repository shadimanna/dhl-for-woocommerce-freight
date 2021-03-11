<?php
/**
 * DHL Freight
 *
 * Checkout form fields
 */

defined('ABSPATH') or exit;

?>

<div class="dhl-freight-cf" style="display: none;">

    <!--@todo We need to allow person to cancel Freight shipment-->
    <h3>DHL Freight</h3>

    <div class="dhl-freight-cf__field-wrap">
        <label for="dhl_freight_selected_service_point">
            <select
                    id="dhl_freight_selected_service_point"
                    class="dhl-freight-cf__field-wrap__field"
            >
                <option selected value=""><?php _e('Select the Service Point', 'pr-shipping-dhl') ?></option>
            </select>
        </label>

        <p class="dhl-freight-cf__field-wrap__noresults" style="display: none"><?php _e('No service points found near the  provided address. Try using search bellow.', 'pr-shipping-dhl') ?></p>
    </div>

    <?php if ($isGoogleMapAvailable) : ?>
        <div class="dhl-freight-cf__search">
            <button
                    id="dhl-fr-find"
                    class="button dhl-freight-cf__search__button"
            >
                <?php _e('Search in Map', 'pr-shipping-dhl') ?>
            </button>
        </div>
    <?php endif ?>

    <input type="hidden" id="dhl-freight-point" name="dhl_freight_selected_service_point" />

    <hr/>
</div>
