// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  saveSlug({ dispatch }, slug) {
    return new Promise((res, rej) => {
      Axios.post(`/organization/save-slug`, { slug: slug })
        .then(response => {
          const body = response.data;

          if (body.success) {
            dispatch("getPreferences");
            res();
          }
        })
        .catch(err => {
          basicCatch(err, "There was an error trying to save URL.");
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
