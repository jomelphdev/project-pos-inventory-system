// import Axios from "axios";
// import { basicCatch } from "./api-helpers";

import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  createAnnouncement(context, announcementData) {
    return new Promise((res, rej) => {
      Axios.post("/admin/notification", announcementData)
        .then(() => {
          res();
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to create announcement."
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
