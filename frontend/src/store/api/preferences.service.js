// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  updatePreference({ dispatch }, { type, preference_id = null, update }) {
    return new Promise((res, rej) => {
      Axios.post(`/preferences/update/preference`, {
        type: type,
        preference_id: preference_id,
        update: update
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            dispatch("getPreferences");
            dispatch("pushNotifications", {
              text: "Preferences updated!",
              type: "success"
            });

            res(body.data.preference);
          }
        })
        .catch(err => {
          basicCatch(err, "There was an error updating your preferences.");

          if (err.data.data) {
            return rej(err.data.data);
          }

          rej();
        });
    });
  },

  updateMultiplePreferences({ dispatch }, preferenceUpdates) {
    return new Promise((res, rej) => {
      Axios.post("/preferences/update/multiple", { updates: preferenceUpdates })
        .then(({ data }) => {
          if (data.success) {
            dispatch("getPreferences");
            dispatch("pushNotifications", {
              text: "Preferences updated!",
              type: "success"
            });

            res();
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to update preferences."
          );
          rej();
        });
    });
  },

  updatePreferences({ dispatch }, update) {
    return new Promise((res, rej) => {
      Axios.post("/preferences/update", update)
        .then(({ data }) => {
          if (data.success) {
            dispatch("getPreferences");
            dispatch("pushNotifications", {
              text: "Preferences updated!",
              type: "success"
            });

            res(data.data.preferences);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to save merchant preferences."
          );
          rej();
        });
    });
  },

  // deleteStation({ dispatch }, stationId) {
  //   return new Promise((res, rej) => {
  //     Axios.post('/preferences/delete/checkout-station', {station_id: stationId})
  //       .then(response => {
  //         const body = response.data;

  //         if (body.success) {
  //           dispatch("getPreferences");
  //           dispatch("pushNotifications", {
  //             text: "Checkout station deleted.",
  //             type: "success"
  //           });

  //           res();
  //         }
  //       })
  //       .catch(err => {
  //         basicCatch(err, 'Something went wrong while trying to delete checkout station.');
  //         rej();
  //       })
  //   })
  // },

  seedDefaultPreferences({ dispatch }, type) {
    return new Promise((res, rej) => {
      Axios.post(`/preferences/seed-default`, {
        default_type: type
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            dispatch("getPreferences");
            dispatch("pushNotifications", {
              text: `Default ${type} created!`,
              type: "success"
            });

            res(body.data.new_preferences);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            `Something went wrong while trying to genereate default ${type}.`
          );
          rej();
        });
    });
  },

  // GET
  getPreferences({ dispatch }, params) {
    Axios.get("/preferences", {
      params: params
    })
      .then(response => {
        const body = response.data;

        if (!body.success) {
          dispatch("logout");
        }

        dispatch("setPreferences", {
          preferences: body.data.preferences,
          organization: body.data.preferences.organization
        });
      })
      .catch(err => {
        basicCatch(
          err,
          "Something went wrong while trying to load preferences."
        );

        // TODO: Might not be needed I think the response interceptor should be enough
        dispatch("logout");
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
