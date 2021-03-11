const billingData = () => {
  const billing_postcode = document.getElementById('billing_postcode')
  const billing_city = document.getElementById('billing_city')
  const billing_address_1 = document.getElementById('billing_address_1')
  const billing_country = document.getElementById('billing_country')

  const getCountryTitle = () => {
    return billing_country.querySelector('[value="' + getCountryField().value + '"]').innerHTML
  }

  const getCountryField = () => {
    return billing_country
  }

  const getPostCodeField = () => {
    return billing_postcode
  }

  const getCityField = () => {
    return billing_city
  }

  const getAddressOneField = () => {
    return billing_address_1
  }

  return {getPostCodeField, getCityField, getAddressOneField, getCountryField, getCountryTitle}
}

export default billingData