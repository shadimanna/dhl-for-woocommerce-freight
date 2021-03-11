const shipData = () => {
  const shipping_country = document.getElementById('shipping_country')
  const shipping_address_1 = document.getElementById('shipping_address_1')
  const shipping_postcode = document.getElementById('shipping_postcode')
  const shipping_city = document.getElementById('shipping_city')

  // Custom field
  const dhl_freight_point_id = document.getElementById('dhl-freight-point')

  const setData = (point) => {
    shipping_country.value = point.countryCode
    shipping_address_1.value = point.street
    shipping_postcode.value = point.postalCode
    shipping_city.value = point.cityName
    dhl_freight_point_id.value = JSON.stringify(point)
  }

  /**
   * Clear all fields except country
   */
  const clear = () => {
    shipping_address_1.value = null
    shipping_postcode.value = null
    shipping_city.value = null
    dhl_freight_point_id.value = null
  }

  return {setData, clear}
}

export default shipData
