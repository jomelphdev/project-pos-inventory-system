const state = {
  laravelUrl: process.env.VUE_APP_LARAVEL_URL
};

export const getters = {
  laravelUrl: serviceState => serviceState.laravelUrl
};

export const actions = {};

export const mutations = {};

export default {
  state,
  getters,
  actions,
  mutations
};
