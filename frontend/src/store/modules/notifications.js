import * as types from "@/store/mutations";

const state = {
  notifications: [],
  dbNotifications: []
};

export const getters = {
  notifications: notificationsState => notificationsState.notifications,
  dbNotifications: notificationsState => notificationsState.dbNotifications
};

export const actions = {
  /*
  notification : {
    text : String,
    type : String 'success' || 'error'
  }
  */
  pushNotifications({ commit }, notifications) {
    if (typeof notifications === "object") notifications = [notifications];

    commit(types.UPDATE_NOTIFICATIONS, notifications);

    setTimeout(() => {
      commit(types.CLEAR_NOTIFICATIONS);
    }, 250);
  },
  setDbNotifications({ commit }, notifications) {
    commit(types.SET_DB_NOTIFICATIONS, notifications);
  },
  shiftDbNotifications({ commit }) {
    commit(types.SHIFT_DB_NOTIFICATIONS);
  }
};

export const mutations = {
  [types.UPDATE_NOTIFICATIONS](notificationsState, notifications) {
    notificationsState.notifications = notifications;
  },
  [types.SET_DB_NOTIFICATIONS](notificationsState, notifications) {
    notificationsState.dbNotifications = notifications;
  },
  [types.SHIFT_DB_NOTIFICATIONS](notificationsState) {
    notificationsState.dbNotifications.shift();
  },
  [types.CLEAR_NOTIFICATIONS](notificationsState) {
    notificationsState.notifications = [];
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
