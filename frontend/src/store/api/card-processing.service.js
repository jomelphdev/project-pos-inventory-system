import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // GET
  verifyMerchant(
    context,
    { merchant_username, merchant_password, merchant_id }
  ) {
    return new Promise((res, rej) => {
      Axios.get(`card/verify`, {
        params: {
          merchant_username,
          merchant_password,
          merchant_id
        }
      })
        .then(({ data }) => {
          if (data.success) {
            res(data.success);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to verify merchant."
          );
          rej();
        });
    });
  },

  getCardTerminals({ getters }) {
    return new Promise((res, rej) => {
      Axios.get(`card/terminals`, {
        params: {
          merchant_id: getters.merchant_id
        }
      })
        .then(({ data }) => {
          if (data.success) {
            res(data.data.terminals);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to retrieve card terminals."
          );
          rej();
        });
    });
  },

  connectToTerminal(context, terminalHsn) {
    return new Promise((res, rej) => {
      Axios.get("card/connect", {
        params: {
          hsn: terminalHsn
        }
      })
        .then(({ data }) => {
          res(data.data.session_key);
        })
        .catch(err => {
          basicCatch(
            err,
            "Failed to create a session with card terminal try again."
          );
          rej();
        });
    });
  },

  disconnectTerminal(context, { hsn, session_key }) {
    return new Promise((res, rej) => {
      Axios.get("card/disconnect", {
        params: {
          hsn,
          session_key
        }
      })
        .then(() => {
          res();
        })
        .catch(err => {
          basicCatch(err, "Was unable to disconnect terminal.");
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
