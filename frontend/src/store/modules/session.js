import * as types from "@/store/mutations";
import router from "@/router";

const state = {
  currentUser: null,
  userRole: null,
  userPermissions: null,
  publicPages: ["login", "home", "create"],
  loggedIn: false,
  errors: [],
  token: null,
  quickBooksAccessToken: null,
  // TODO: Store verified in Laravel. I think it uses a timestamp verified_at so may need to adjust.
  verified: false,
  subscriptionRequired: false,
  updatedPaymentMethodRequired: false
};

export const getters = {
  loggedIn: sessionState => sessionState.loggedIn,
  currentUser: sessionState => sessionState.currentUser,
  errors: sessionState => sessionState.errors,
  token: sessionState => sessionState.token,
  quickBooksAccessToken: sessionState => sessionState.quickBooksAccessToken,
  verified: sessionState => sessionState.verified,
  userPermissions: sessionState => sessionState.userPermissions,
  userRole: sessionState => sessionState.userRole,
  publicPages: sessionState => sessionState.publicPages,
  subscriptionRequired: sessionState => sessionState.subscriptionRequired,
  updatedPaymentMethodRequired: sessionState =>
    sessionState.updatedPaymentMethodRequired
};

export const actions = {
  login({ commit, dispatch }, { username, password }) {
    commit(types.LOGIN_REQUEST);
    dispatch("authenticateUser", { username, password });
  },

  logout({ commit, dispatch }) {
    commit(types.LOGOUT_REQUEST);
    commit(types.CLEAR_PREFERENCES);
    commit(types.UNVERIFY_ACCOUNT);
    commit(types.SET_DB_NOTIFICATIONS, []);
    dispatch("setLoadingReport", false);
    router.go("/login");
  },

  verifyAccount({ commit, getters }) {
    commit(types.VERIFY_ACCOUNT);

    if (getters.userRole.name == "admin") {
      return router.push({ name: "admin" });
    }

    router.push({ name: "scan" });
  },

  unverifyAccount({ commit }) {
    commit(types.UNVERIFY_ACCOUNT);
  },

  setUser({ commit, dispatch }, user) {
    commit(types.SET_USER, user);

    if (!user.email_verified_at) {
      this.$store.dispatch("unverifyAccount");
      router.push({ name: "verify" });
    }

    dispatch("verifyAccount");
  }
};

export const mutations = {
  [types.LOGIN_REQUEST]() {},
  [types.AUTHENTICATION_SUCCESS](sessionState, { currentUser, token }) {
    sessionState.currentUser = currentUser;
    sessionState.subscriptionRequired = currentUser.subscription_required;
    sessionState.updatedPaymentMethodRequired =
      currentUser.updated_payment_method_required;
    sessionState.userRole = currentUser.user_role;
    sessionState.userPermissions = currentUser.user_permissions;
    sessionState.loggedIn = true;
    sessionState.errors = [];
    sessionState.token = token;
  },
  [types.AUTHENTICATION_FAILURE](sessionState, { errors }) {
    sessionState.currentUser = null;
    sessionState.loggedIn = false;
    sessionState.errors = errors;
  },
  [types.SET_SUBSCRIPTION_REQUIRED](sessionState, bool) {
    sessionState.subscriptionRequired = bool;
    sessionState.updatedPaymentMethodRequired = bool;
  },
  [types.SET_TOKEN](sessionState, token) {
    sessionState.token = token;
  },
  [types.SET_QUICKBOOKS_ACCESS_TOKEN](sessionState, token) {
    sessionState.quickBooksAccessToken = token;
  },
  [types.LOGOUT_REQUEST](sessionState) {
    sessionState.currentUser = null;
    sessionState.userRole = null;
    sessionState.userPermissions = null;
    sessionState.loggedIn = false;
    sessionState.errors = [];
    sessionState.token = null;
  },
  [types.VERIFY_ACCOUNT](sessionState) {
    sessionState.verified = true;
  },
  [types.UNVERIFY_ACCOUNT](sessionState) {
    sessionState.verified = false;
  },
  [types.SET_USER](sessionState, user) {
    sessionState.currentUser = user;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
