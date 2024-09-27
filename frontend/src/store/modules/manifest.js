import * as types from "@/store/mutations";

const state = {
  manifest: null
};

export const getters = {
  manifest: manifestState => manifestState.manifest
};

export const actions = {
  setManifest({ commit }, manifest) {
    commit(types.SET_MANIFEST, manifest);
  }
};

export const mutations = {
  [types.SET_MANIFEST](manifestState, manifest) {
    manifestState.manifest = manifest;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
