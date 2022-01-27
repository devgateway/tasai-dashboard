import { get } from '../../api/commons'
import {
  COUNTRY_INFO,
  NUMBER_OF_VARIETIES_RELEASED,
  SELECTED_COUNTRY,
  MARKET_CONCENTRATION_HHI,
  VARIETIES_RELEASED_WITH_SPECIAL_FEATURES,
  NUMBER_VARIETIES_SOLD,
  PERFORMANCE_SEED_TRADERS,
  AVAILABILITY_OF_BASIC_SEED, DEFAULT_COUNTRY_ID,
  AVERAGE_AGE_VARIETIES_SOLD,
  NUMBER_OF_ACTIVE_BREEDERS, WP_CHART,
} from "./StoreConstants";

const SURVEY_API = process.env.REACT_APP_SURVEY_API
const IP_INFO_URL = 'https://ipinfo.io/json?token=145d05e17c7c25';
const APP_WP_API = process.env.REACT_APP_WP_API;
//TODO add parameters as config
const WP_CATEGORIES = APP_WP_API + '/wp/v2/categories?per_page=100&_locale=user';
const POLICY_API_ROOT = process.env.REACT_APP_POLICY_API
const SURVEY_FILTER_API = 'filter';
const DATA_SUMMARY = 'dataSummary';
const SURVEY_COUNTRIES_API = `${SURVEY_API}/${SURVEY_FILTER_API}/`
const SURVEY_INDICATORS_API = `${SURVEY_API}/${SURVEY_FILTER_API}/indicators`
const SURVEY_INDICATOR_INFORMATION_API = `${SURVEY_API}/${DATA_SUMMARY}/categoryId/{categoryId}/latest`;
const WP_DOCUMENTS_API = `${APP_WP_API}/wp/v2/media`;

let COUNTRY_INFORMATION_API = `${SURVEY_API}/countryInfo/countryId/`;
let NUMBER_OF_VARIETIES_RELEASED_API = `${SURVEY_API}/chart/numberVarietiesReleased/year/crop`;
let VARIETIES_RELEASED_WITH_SPECIAL_FEATURES_API = `${SURVEY_API}/chart/cropsReleased/crop/year`;
let AVERAGE_AGE_VARIETIES_SOLD_API = `${SURVEY_API}/chart/averageAgeVarietiesSold/crop/year`;
let AVAILABILITY_OF_BASIC_SEED_API = `${SURVEY_API}/chart/availabilityBasicSeed/crop/year/`;
let NUMBER_VARIETIES_SOLD_API = `${SURVEY_API}/chart/numberVarietiesSold/crop/year/`;
let PERFORMANCE_SEED_TRADERS_API = `${SURVEY_API}/chart/performanceSeedTraders/performance/year/`;
const NUMBER_OF_ACTIVE_BREEDERS_API = `${SURVEY_API}/chart/numberActiveBreeders/year/crop/`;
const MARKET_CONCENTRATION_HHI_API = `${SURVEY_API}/chart/marketConcentration/crop/year/`;

const APIS = {
  prevalence: '',
  policy: POLICY_API_ROOT,
  [COUNTRY_INFO]: COUNTRY_INFORMATION_API,
  [MARKET_CONCENTRATION_HHI]: MARKET_CONCENTRATION_HHI_API,
  [NUMBER_OF_VARIETIES_RELEASED]: NUMBER_OF_VARIETIES_RELEASED_API,
  [VARIETIES_RELEASED_WITH_SPECIAL_FEATURES]: VARIETIES_RELEASED_WITH_SPECIAL_FEATURES_API,
  [AVERAGE_AGE_VARIETIES_SOLD]: AVERAGE_AGE_VARIETIES_SOLD_API,
  [AVAILABILITY_OF_BASIC_SEED]: AVAILABILITY_OF_BASIC_SEED_API,
  [NUMBER_OF_ACTIVE_BREEDERS]: NUMBER_OF_ACTIVE_BREEDERS_API,
  [NUMBER_VARIETIES_SOLD]: NUMBER_VARIETIES_SOLD_API,
  [PERFORMANCE_SEED_TRADERS]: PERFORMANCE_SEED_TRADERS_API
}

function queryParams(params) {
  return Object.keys(params)
    .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
    .join('&')
}

export const getCountriesData = (dataSource, params) => {
  return get(SURVEY_COUNTRIES_API + dataSource, params)
}
export const getCategoriesWP = (params) => {
  return get(WP_CATEGORIES, params)
}
export const getIndicatorsData = (params) => {
  return get(SURVEY_INDICATORS_API, params)
}

export const getData = ({ source, app, params }) => {
  let sources;
  if (source) {
    sources = source.split("|");
  }

  if (app === COUNTRY_INFO && params && (params[SELECTED_COUNTRY] || params[DEFAULT_COUNTRY_ID])) {
    let countryId = params[DEFAULT_COUNTRY_ID];
    if (params[SELECTED_COUNTRY]) {
      countryId = params[SELECTED_COUNTRY];
      delete params[DEFAULT_COUNTRY_ID];
    }
    return get(APIS[app] + countryId);
  } else if (app === NUMBER_OF_VARIETIES_RELEASED
    || app === AVAILABILITY_OF_BASIC_SEED
    || app === MARKET_CONCENTRATION_HHI  
    || app === VARIETIES_RELEASED_WITH_SPECIAL_FEATURES
    || app === AVERAGE_AGE_VARIETIES_SOLD
    || app === NUMBER_OF_ACTIVE_BREEDERS
    || app === VARIETIES_RELEASED_WITH_SPECIAL_FEATURES
    || app === NUMBER_VARIETIES_SOLD
    || app === PERFORMANCE_SEED_TRADERS
    || (sources && sources.length > 0 && sources[0] === WP_CHART)
  ) {
    if (params[SELECTED_COUNTRY] || params[DEFAULT_COUNTRY_ID]) {
      params.countryId = params[DEFAULT_COUNTRY_ID];
      if (params[SELECTED_COUNTRY]) {
        params.countryId = params[SELECTED_COUNTRY];
        delete params[DEFAULT_COUNTRY_ID];
      }
      let api;
      if (sources && sources[0] === WP_CHART) {
        api = `${SURVEY_API}/chart/${app}/${sources[1]}/${sources[2]}`;
      } else {
        api = APIS[app];
      }
      const apiToCall = api + (params ? '?' + queryParams(params) : '');
      return get(apiToCall);
    } else {
      // TODO: remove this after we are sure we will always use the country filter component.
      return Promise.resolve();
    }
  } else {
    return get(APIS[app] + (params ? '?' + queryParams(params) : ''))
  }
}

export function getIndicatorsInformation(categoryId) {
  return get(SURVEY_INDICATOR_INFORMATION_API.replace("{categoryId}", categoryId))
}

export const loadCountrySettings = () => {
  return get(IP_INFO_URL);
}

export const getDocumentsData = (params) => {
  return get(WP_DOCUMENTS_API, params)
}

