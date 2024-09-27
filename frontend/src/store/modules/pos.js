import * as types from "@/store/mutations";

const state = {
  posStore: null,
  posStation: null
};

export const getters = {
  posStore: posState => posState.posStore,
  posStation: posState => posState.posStation
};

export const actions = {
  selectStore({ commit }, store) {
    commit(types.UPDATE_POS_STORE, store);
  },
  selectStation({ commit }, station) {
    commit(types.UPDATE_POS_STATION, station);
  },
  clearPos({ commit }) {
    commit(types.CLEAR_POS);
  }
};

export const mutations = {
  [types.UPDATE_POS_STORE](posState, store) {
    // if (store.receiptOptions.storeLogo) {
    //   store.receiptOptions.storeLogo =
    //     "http://www.harvardhoodie.com/RetailRight/" +
    //     store.receiptOptions.storeLogo;
    // }
    posState.posStore = store;
  },
  [types.UPDATE_POS_STATION](posState, station) {
    posState.posStation = station;
  },
  [types.CLEAR_POS](posState) {
    posState.posStore = null;
    posState.posStation = null;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
