// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  uploadImage(context, image) {
    return new Promise((res, rej) => {
      let formData = new FormData();
      formData.append("image", image);

      Axios.post(`images/upload`, formData)
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.image_url);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to upload images."
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
