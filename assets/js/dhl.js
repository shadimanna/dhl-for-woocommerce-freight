// Deps
window.axios = require('axios').default
window.qs = require('qs')

import fsp from "./freight-service-point/fsp";

// Run Actions
const actions = () => {
  const run = () => {
    fsp().init()
  }

  return {run}
}

// On jQuery done run all actions
jQuery(document).ready(() => actions().run());