<?php
/**
 * DHL Freight
 *
 * Checkout search in map markup
 */

defined( 'ABSPATH' ) or exit;
?>

<div id="dhl-freight-finder" class="dhl-freight-popup">
    <div class="dhl-freight-popup__cont">
        <h4>Service Point Finder</h4>

        <div class="dhl-freight-popup__form">
            <label for="dhl_freight_city" class="">
                City
                <input type="text" id="dhl_freight_city" class="dhl-freight-popup__form-field" autocomplete="city" />
            </label>
            <label for="dhl_freight_postal_code" class="">
                Post Code
                <input type="text" id="dhl_freight_postal_code" class="dhl-freight-popup__form-field" autocomplete="postal_code" />
            </label>
        </div>

        <div class="dhl-freight-popup__form-actions">
            <button class="dhl-freight-popup__search">Search</button>
        </div>

        <div id="dhl-freight-map" class="dhl-freight-popup__map"></div>
        <div class="dhl-freight-popup__actions">
            <button class="dhl-freight-popup__close">Close</button>
        </div>
    </div>
</div>
