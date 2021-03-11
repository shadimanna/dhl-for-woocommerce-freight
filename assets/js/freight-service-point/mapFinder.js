import shipData from "./shipData";
import locationService from "./services/locationService";

const mapFinder = () => {
  const googleMapElem = document.getElementById('dhl-freight-map')
  const googleMapFindButton = document.getElementById('dhl-fr-find')
  const popUpElem = document.getElementById('dhl-freight-finder')
  const popUpCloseButton = popUpElem.querySelector('.dhl-freight-popup__close')
  const popUpSearchButton = popUpElem.querySelector('.dhl-freight-popup__search')

  const cityField = document.getElementById('dhl_freight_city')
  const postalCodeField = document.getElementById('dhl_freight_postal_code')

  let map;
  let markers = [];

  const popUpVisual = () => {
    const open = () => {
      popUpElem.style.display = 'block'
    }

    const close = () => {
      popUpElem.style.display = 'none'
    }

    return {open, close}
  }

  const isEmptyForm = () => {
    return ! cityField.value && ! postalCodeField.value
  }

  /**
   * Open finder popup
   * @param e
   */
  const openFinder = (e) => {
    e.preventDefault();

    popUpVisual().open()
    initMap()

    if (isEmptyForm()) {
      cityField.value = billingData.getCityField().value
      postalCodeField.value = billingData.getPostCodeField().value
    }
  }

  /**
   * Close finder popup
   * @param e
   */
  const closeFinder = (e) => {
    e.preventDefault()

    popUpVisual().close()
  }

  const addMarker = (point) => {
    const marker = new google.maps.Marker({
      position: {
        lat: point.latitude,
        lng: point.longitude
      },
      map: map
    });

    marker.set('point', point)

    marker.addListener('click', function() {
      const point = marker.get('point')

      // Update shipping address
      shipData().setData(point)

      // Make dropdown valu to be same
      locationSelector.setValue(point.id)

      popUpVisual().close()
    });

    markers.push(marker)
  }

  const removeMarkers = () => {
    for (let i = 0; i < markers.length; i++) {
      markers[i].setMap(null);
    }
  }

  /**
   * Add Points
   */
  const addPoints = (points) => {
    points.forEach(function (point) {
      addMarker(point)
    })
  }

  const updateMap = () => {
    locationService.request({
      postalCode: postalCodeField.value,
      city: cityField.value,
    })
        .then(function (response) {
          removeMarkers()

          if (response.data.error) {
            return
          }

          if (response.data.length > 0) {
            addPoints(response.data)

            // Set dropdown values
            locationSelector.setOptions(response.data)
          }
        })
        .catch(function () {
          removeMarkers()
        })
  }

  /**
   * Initialize Map
   */
  const initMap = () => {
    const geocoder = new google.maps.Geocoder();

    geocoder.geocode({
      'address': billingData.getPostCodeField().value + ' ' + billingData.getCountryTitle()
    }, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        map = new google.maps.Map(googleMapElem, {
          zoom: 13,
          center: results[0].geometry.location,
          disableDefaultUI: true,
          zoomControl: true,
        });

        updateMap()
      }
    })
  }

  /**
   * Initialization
   */
  const init = () => {
    // Trigger map click
    if (! popUpElem) {
      return
    }

    if (googleMapFindButton) {
      googleMapFindButton.addEventListener('click', openFinder)
    }

    popUpCloseButton.addEventListener('click', closeFinder)
    popUpSearchButton.addEventListener('click', updateMap)
  }

  init();

  return {addPoints}
}

export default mapFinder;
