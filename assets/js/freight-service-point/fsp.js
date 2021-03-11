import mapFinder from "./mapFinder";
import availability from "./availability";
import locationSelector from "./locationSelector";
import billingData from "./billingData";

const fsp = () => {
  /**
   * Initialize functionality
   */
  const init = () => {
    // Dropdown
    // @todo refactor window vars
    window.billingData = billingData()
    window.locationSelector = locationSelector()
    window.mapFinder = mapFinder()

    availability().init();
  }

  return {init}
}

export default fsp;