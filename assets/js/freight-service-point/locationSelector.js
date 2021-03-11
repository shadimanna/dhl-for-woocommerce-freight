import locationService from "./services/locationService";
import shipData from "./shipData";

const locationSelector = () => {
  const field = document.getElementById('dhl_freight_selected_service_point')
  const noResultsNotice = document.querySelector('.dhl-freight-cf__field-wrap__noresults')

  let data = [];

  // @todo Implement cache in future
  let cached = [];

  const clearOptions = () => {
    // Clear options
    field.innerHTML = ''

    // Add select point option
    let option = document.createElement("option")

    option.text = 'Select the service point'
    option.value = ''
    option.selected = true

    field.appendChild(option);
  }

  const disableField = () => {
    field.style.display = 'none'
  }

  const enableField = () => {
    field.style.display = 'block'
  }

  const showNoResults = () => {
    noResultsNotice.style.display = 'block'
  }

  const hideNoResults = () => {
    noResultsNotice.style.display = 'none'
  }

  /**
   * Set Value
   *
   * @param e
   */
  const setFields = (e) => {
    if (! e.target.value) {

      shipData().clear()

      return
    }

    const point = getPoint(e.target.value)

    shipData().setData(point)
  }

  const setValue = (id) => {
    field.value = id
  }

  const setOptions = (points) => {
    clearOptions()

    data = []

    // Add new
    points.forEach(function (point) {
      let option = document.createElement("option");

      option.text = point.name;
      option.value = point.id;

      field.appendChild(option);

      data[point.id] = point
    })
  }

  const getPoint = (id) => {
    return data[id]
  }

  const getOptions = () => {
    return data
  }

  const loadValues = () => {
    return new Promise(function (resolve, reject) {
      locationService.request({
        postalCode: billingData.getPostCodeField().value,
        city: billingData.getCityField().value,
      })
          .then(function (response) {
            if (response.data.error) {
              reject(response.data.error)

              return
            }

            if (response.data.length > 0) {
              hideNoResults()
              enableField()
              setOptions(response.data)
            } else {
              clearOptions()
              disableField()
              showNoResults()
            }

            resolve()
          })
          .catch(function () {
            reject()
          })
    })
  }

  /**
   * Initialize functionality
   */
  const init = () => {
    if (! field) {
      return
    }

    field.addEventListener('change', setFields)
  }

  init();

  return {loadValues, getOptions, setValue, setOptions}
}

export default locationSelector;
