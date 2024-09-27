import * as types from "@/store/mutations";
import { mergeList } from "../../helpers";

const state = {
  classifications: [],
  conditions: [],
  discounts: [],
  stations: [],
  stores: [],
  employees: [],
  consignors: [],
  all_permissions: null,
  organization: null,
  organization_id: null,
  subscription: null,
  version: null,
  newVersion: null,
  paymentPartner: false,
  merchant_id: null,
  hide_pos_sales: false,
  classifications_disabled: false,
  conditions_disabled: false,
};

export const getters = {
  classifications: (preferencesState) => preferencesState.classifications,
  classificationsVisible: (preferencesState) =>
    preferencesState.classifications
      .filter((c) => {
        return !c.deleted_at;
      })
      .sort((a, b) => b.times_used - a.times_used),
  nonTaxedIds: (preferencesState) =>
    preferencesState.classifications
      .filter((c) => c.isTaxed === false)
      .map((c) => {
        return c.id;
      }),
  ebtIds: (preferencesState) =>
    preferencesState.classifications
      .filter((c) => c.isEbt)
      .map((c) => {
        return c.id;
      }),
  conditions: (preferencesState) => preferencesState.conditions,
  conditionsVisible: (preferencesState) =>
    preferencesState.conditions
      .filter((c) => {
        return !c.deleted_at;
      })
      .sort((a, b) => b.times_used - a.times_used),
  discounts: (preferencesState) => preferencesState.discounts,
  discountsVisible: (preferencesState) =>
    preferencesState.discounts
      .filter((d) => {
        return !d.deleted_at;
      })
      .sort((a, b) => b.times_used - a.times_used),
  stations: (preferencesState) => preferencesState.stations,
  stationsVisible: (preferencesState) =>
    preferencesState.stations.filter((station) => !station.deleted_at),
  stores: (preferencesState) => preferencesState.stores,
  storesVisible: (preferencesState) =>
    preferencesState.stores.filter((s) => {
      return !s.deleted_at;
    }),
  states: (preferencesState) => preferencesState.states,
  all_permissions: (preferencesState) => preferencesState.all_permissions,
  organization: (preferencesState) => preferencesState.organization,
  organization_id: (preferencesState) => preferencesState.organization_id,
  subscription: (preferencesState) => preferencesState.subscription,
  employees: (preferencesState) => preferencesState.employees,
  consignors: (preferencesState) => preferencesState.consignors,
  consignorsVisible: (preferencesState) =>
    preferencesState.consignors.filter((c) => {
      return !c.deleted_at;
    }),
  preference_id: (preferencesState) => preferencesState.preference_id,
  version: (preferencesState) => preferencesState.version,
  newVersion: (preferencesState) => preferencesState.newVersion,
  updateAvailable: (preferencesState) =>
    preferencesState.newVersion &&
    preferencesState.newVersion != preferencesState.version,
  paymentPartner: (preferencesState) => preferencesState.paymentPartner,
  merchant_username: (preferencesState) => preferencesState.merchant_username,
  merchant_password: (preferencesState) => preferencesState.merchant_password,
  merchant_id: (preferencesState) => preferencesState.merchant_id,
  hide_pos_sales: (preferencesState) => preferencesState.hide_pos_sales,
  classifications_disabled: (preferencesState) =>
    preferencesState.classifications_disabled,
  conditions_disabled: (preferencesState) =>
    preferencesState.conditions_disabled,
  isUsingQuickBooks: (preferencesState) =>
    preferencesState.organization.is_quickbooks_in_use,
  isQuickBooksAuthenticated: (preferencesState) =>
    preferencesState.organization.is_quickbooks_authenticated,
};

export const actions = {
  setPreferences({ commit, dispatch }, { preferences, organization }) {
    if (organization) dispatch("setOrganization", organization);

    commit(types.UPDATE_PREFERENCES, { preferences });
    dispatch("setVersion", preferences.version);
  },

  setOrganization({ commit, dispatch }, org) {
    commit(types.SET_ORGANIZATION, org);
    if (org.subscription) dispatch("updateSubscription", org.subscription);
  },

  setVersion({ commit, getters }, version) {
    // This is only for first log-in.
    if (!getters.version) {
      return commit(types.SET_VERSION, version);
    }

    if (version != getters.version) {
      commit(types.SET_NEW_VERSION, version);
    }
  },

  applyUpdate({ commit, getters }) {
    commit(types.SET_VERSION, getters.newVersion);
    window.location.reload();
  },

  updateSubscription({ commit }, subscription) {
    commit(types.UPDATE_SUBSCRIPTION, subscription);
  },

  clearPreferences({ commit }) {
    commit(types.CLEAR_PREFERENCES);
  },
};

export const mutations = {
  [types.UPDATE_PREFERENCES](preferencesState, { preferences }) {
    preferencesState.classifications = mergeList(
      preferencesState.classifications,
      preferences.classifications
    );
    preferencesState.conditions = mergeList(
      preferencesState.conditions,
      preferences.conditions
    );
    preferencesState.discounts = mergeList(
      preferencesState.discounts,
      preferences.discounts
    );
    preferencesState.stations = preferences.checkout_stations;
    preferencesState.stores = preferences.stores;
    preferencesState.all_permissions = preferences.all_permissions;
    preferencesState.employees = preferences.employees_with_permissions
      ? preferences.employees_with_permissions
      : preferencesState.employees;
    preferencesState.consignors = preferences.consignors;
    preferencesState.organization_id = preferences.organization_id;
    preferencesState.preference_id = preferences.id;
    preferencesState.paymentPartner = preferences.using_merchant_partner;
    preferencesState.merchant_username = preferences.merchant_username;
    preferencesState.merchant_password = preferences.merchant_password;
    preferencesState.merchant_id = preferences.merchant_id;
    preferencesState.hide_pos_sales = preferences.hide_pos_sales;
    preferencesState.classifications_disabled =
      preferences.classifications_disabled;
    preferencesState.conditions_disabled = preferences.conditions_disabled;

    if (preferences.states) {
      preferencesState.states = preferences.states;
    }
  },
  [types.SET_ORGANIZATION](preferencesState, org) {
    preferencesState.organization = org;
  },
  [types.SET_ORGANIZATION_ID](preferencesState, orgId) {
    preferencesState.organization_id = orgId;
  },
  [types.SET_VERSION](preferencesState, version) {
    preferencesState.version = version;
    preferencesState.newVersion = null;
  },
  [types.SET_NEW_VERSION](preferencesState, version) {
    preferencesState.newVersion = version;
  },
  [types.UPDATE_SUBSCRIPTION](preferencesState, subscription) {
    preferencesState.subscription = subscription;
  },
  [types.CLEAR_PREFERENCES](preferencesState) {
    preferencesState.classifications = [];
    preferencesState.conditions = [];
    preferencesState.discounts = [];
    preferencesState.stations = [];
    preferencesState.stores = [];
    preferencesState.states = [];
    preferencesState.all_permissions = null;
    preferencesState.organization = null;
    preferencesState.organization_id = null;
    preferencesState.subscription = null;
    preferencesState.preference_id = null;
    preferencesState.employees = [];
    preferencesState.paymentPartner = false;
    preferencesState.merchant_id = null;
    preferencesState.hide_pos_sales = false;
    preferencesState.classifications_disabled = false;
    preferencesState.conditions_disabled = false;
  },
};

export default {
  state,
  getters,
  actions,
  mutations,
};
