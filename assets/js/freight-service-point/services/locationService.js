class LocationService {
  constructor() {
    this.action = 'dhl_service_point_search'
  }

  request(params) {
    return axios.post(dhl.ajax_url, qs.stringify({
      action: this.action,
      security: dhl.ajax_nonce,
      dhl_freight_postal_code: params.postalCode,
      dhl_freight_city: params.city,
    }))
  }
}

export default new LocationService()
