// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  resendEmailVerification({ getters, dispatch }) {
    Axios.post(`verification/resend/${getters.currentUser.id}`)
      .then(() => {
        dispatch("pushNotifications", {
          text: "E-mail verification sent.",
          type: "success"
        });
      })
      .catch(err => {
        basicCatch(
          err,
          "Unable to resend e-mail verification at this time, make sure you input a valid e-mail and try again."
        );
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
