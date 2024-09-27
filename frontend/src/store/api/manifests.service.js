// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  uploadManifest(context, { file, manifest }) {
    let formData = new FormData();
    formData.append("manifest", file);
    formData.append("manifest_name", manifest);

    return new Promise((res, rej) => {
      Axios.post(`/manifests/upload`, formData)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res();
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to upload your manifest."
          );
          rej();
        });
    });
  },

  queryManifestItems(context, { manifestId, query }) {
    return new Promise((res, rej) => {
      Axios.post(`manifests/query/${manifestId}`, { query: query })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.items);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to query manifest items."
          );
          rej();
        });
    });
  },

  archiveManifest(context, manifestId) {
    return new Promise((res, rej) => {
      Axios.post(`manifests/archive/${manifestId}`)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res();
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to archive manifest."
          );
          rej();
        });
    });
  },

  // GET
  getManifests() {
    return new Promise((res, rej) => {
      Axios.get(`manifests`)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.manifests);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get manifests."
          );
          rej();
        });
    });
  },
};

export const mutations = {};

export default {
  state,
  getters,
  actions,
  mutations,
};
