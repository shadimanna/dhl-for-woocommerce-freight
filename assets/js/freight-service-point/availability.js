const availability = () => {
  const fields = document.querySelector('.dhl-freight-cf')
  const requiredFieldIds = [
    'billing_postcode',
    'billing_country',
  ]

  // @todo move to the setting parameter
  const mustHaveCountry = dhl.shopCountry.country
  /**
   * Disable Freight
   */
  const disable = () => {
    fields.style.display = 'none'
  }

  /**
   * Enable Freight
   */
  const enable = () => {
    locationSelector.loadValues().then(function () {
      fields.style.display = 'block'
    })
  }

  /**
   * Checker if all required fields set
   */
  const check = () => {
    let available = true;

    requiredFieldIds.forEach(function (fieldId) {
      let field = document.getElementById(fieldId)

      // If no value set atleast in one of the fields disable flow
      if (!field.value) {
        available = false
      }

      // If country field check if its mustHaveCountry
      if (
          fieldId === 'billing_country' &&
          field.value !== mustHaveCountry
      ) {
        available = false
      }
    })


    if (!available) {
      disable()

      return;
    }

    enable()
  }

  /**
   * Initialize functionality
   */
  const init = () => {
    check()

    requiredFieldIds.forEach(function (fieldId) {
      let field = document.getElementById(fieldId)

      // Has to be jQuery due to select2 which would require enableNativeEvent
      jQuery(field).on('change', function () {
        check()
      })
    })
  }

  return {init, check}
}

export default availability;