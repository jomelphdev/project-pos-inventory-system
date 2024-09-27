import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";
import router from "@/router";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  createUser({ dispatch, commit }, { user, adminUser = false }) {
    return new Promise((res, rej) => {
      Axios.post("/users/create", user)
        .then(response => {
          const body = response.data;
          if (body.success) {
            const user = body.data.user;

            if (adminUser) {
              commit(types.SET_TOKEN, `Bearer ${user.token}`);
              dispatch("getPreferences");
            } else {
              dispatch("getPreferences", {
                append: "employees_with_preferences"
              });
            }

            dispatch("pushNotifications", {
              text: "User has been created.",
              type: "success"
            });

            res(user);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to create new user."
          );
          rej();
        });
    });
  },

  authenticateUser({ commit, getters, dispatch }, userCredentials) {
    Axios.post("/users/authenticate", userCredentials)
      .then(({ data }) => {
        let user = data.data.user;

        commit(types.AUTHENTICATION_SUCCESS, {
          currentUser: user,
          token: `Bearer ${user.token}`
        });

        dispatch("setPreferences", {
          preferences: user.organization.preferences,
          organization: user.organization
        });

        if (user.unread_notifications) {
          commit(types.SET_DB_NOTIFICATIONS, user.unread_notifications);
        }

        if (!user.email_verified_at) {
          return router.push({ name: "verify" });
        }

        dispatch("verifyAccount");

        setTimeout(() => {
          if (getters.newVersion) {
            dispatch("applyUpdate");
          }
        }, 250);
      })
      .catch(err => {
        basicCatch(err, "Something went wrong while trying to login.");
      });
  },

  updateUser({ dispatch, getters }, { userId, update }) {
    return new Promise((res, rej) => {
      Axios.post(`/users/update/${userId}`, { update: update })
        .then(response => {
          const body = response.data;

          if (body.success) {
            const user = body.data.user;

            if (user.id == getters.currentUser.id) {
              dispatch("setUser", user);
            }

            dispatch("getPreferences", {
              append: "employees_with_permissions"
            });
            dispatch("pushNotifications", {
              text: "User has been updated.",
              type: "success"
            });

            res(user);
          }
        })
        .catch(err => {
          basicCatch(err, "Something went wrong while trying to update user.");
          rej();
        });
    });
  },

  verifyPassword(root, { userId, password }) {
    return new Promise((res, rej) => {
      Axios.post("/users/verify-password", {
        user_id: userId,
        password: password
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.data.isMatch);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to verify password."
          );
          rej();
        });
    });
  },

  readNotification(context, notificationId) {
    return new Promise((res, rej) => {
      Axios.post("/users/notification", {
        notification_id: notificationId
      })
        .then(() => {
          res(true);
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to update notification."
          );
          rej();
        });
    });
  },

  // GET
  getUser(root, userId) {
    return new Promise((res, rej) => {
      Axios.get(`/users/${userId}`)
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.data.user);
          }
        })
        .catch(err => {
          basicCatch(err, "Something went wrong while trying to get user.");
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
