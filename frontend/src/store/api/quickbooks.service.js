import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  generateJournalEntry({ dispatch }) {
    return new Promise((res, rej) => {
      Axios.post("quickbooks/generate-journal")
        .then(({ data }) => {
          const body = data.data;

          dispatch("pushNotifications", {
            text: body.message,
            type: "success"
          });

          res();
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to generate journal entry."
          );
          rej();
        });
    });
  },

  revokeQuickBooksAccess({ dispatch }) {
    return new Promise((res, rej) => {
      Axios.post("quickbooks/revoke")
        .then(({ data }) => {
          const body = data.data;

          dispatch("getPreferences");
          dispatch("pushNotifications", {
            text: body.message,
            type: "success"
          });

          res();
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to revoke application access to QuickBooks."
          );
          rej();
        });
    });
  },

  // GET
  authorizeQuickBooks() {
    return new Promise((res, rej) => {
      Axios.get(`quickbooks/authorize`)
        .then(({ data }) => {
          if (data.success) {
            res(data.data.auth_url);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to connect to QuickBooks."
          );
          rej();
        });
    });
  },

  getQuickBooksAccessTokens({ commit, dispatch }, { auth, realm_id }) {
    return new Promise((res, rej) => {
      Axios.get("quickbooks/access-token", {
        params: {
          auth: auth,
          realm_id: realm_id
        }
      })
        .then(({ data }) => {
          dispatch("getPreferences");
          commit(types.SET_QUICKBOOKS_ACCESS_TOKEN, data.data.access_token);
          res();
        })
        .catch(err => {
          basicCatch(err, "Unable to retrieve an access token from QuickBooks");
          rej();
        });
    });
  },

  getExistingJournals(context, page) {
    return new Promise((res, rej) => {
      Axios.get("quickbooks/journals", {
        params: {
          page: page
        }
      })
        .then(({ data }) => {
          const body = data.data;

          res(body.journals);
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to fetch existing QuickBooks journals."
          );
          rej();
        });
    });
  }
};

export const mutations = {};

export default {
  state,
  getters,
  actions,
  mutations
};
