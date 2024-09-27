import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {
  session_id: null,
  payment_method: null
};

export const getters = {
  session_id: state => state.session_id,
  payment_method: state => state.payment_method
};

export const actions = {
  // ROUTES
  // POST
  createCheckoutSession({ commit }) {
    return new Promise((res, rej) => {
      Axios.post(`/stripe/create-checkout`)
        .then(response => {
          const body = response.data;

          if (body.success) {
            const sessionId = body.data.session_id;
            commit(types.SET_SESSION_ID, sessionId);
            res(sessionId);
          }
        })
        .catch(err => {
          basicCatch(err, "Something went wrong while trying to create user.");
          rej();
        });
    });
  },

  changeSubscriptionPlan({ getters, dispatch, commit }, { productId, planId }) {
    return new Promise((res, rej) => {
      Axios.post("/stripe/change-plan", {
        organization_id: getters.organization_id,
        product_id: productId,
        plan_id: planId
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            const data = body.data;
            const newPlan = data.new_plan;

            dispatch("updateSubscription", newPlan);
            commit(types.SET_SUBSCRIPTION_REQUIRED, false);

            res(newPlan);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to change subscription plan."
          );
          rej();
        });
    });
  },

  cancelSubscription({ getters, dispatch }) {
    return new Promise((res, rej) => {
      Axios.post("/stripe/cancel-plan", {
        organization_id: getters.organization_id
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            const canceledPlan = body.data.canceled_plan;

            dispatch("updateSubscription", canceledPlan);
            dispatch("pushNotifications", {
              text: "Account has successfully been canceled.",
              type: "success"
            });
            res();
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to cancel subscscription"
          );
          rej();
        });
    });
  },

  updatePaymentMethod({ commit, getters, dispatch }, paymentMethod) {
    return new Promise((res, rej) => {
      Axios.post("/stripe/update-payment-method", {
        organization_id: getters.organization_id,
        payment_method: paymentMethod
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            commit(types.SET_PAYMENT_METHOD, paymentMethod);

            if (getters.updatePaymentMethodRequired) {
              commit(types.SET_SUBSCRIPTION_REQUIRED, false);
            }

            dispatch("getPreferences");
            dispatch("pushNotifications", {
              text: "Payment method updated successfully!",
              type: "success"
            });

            res(paymentMethod);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to update payment method."
          );
          rej();
        });
    });
  },

  // GET
  fetchSubscriptionPlans() {
    return new Promise((res, rej) => {
      Axios.get("/stripe/subscription-plans")
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.data.subscription_plans);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to get subscription options."
          );
          rej();
        });
    });
  }
};

export const mutations = {
  [types.SET_SESSION_ID](state, session_id) {
    state.session_id = session_id;
  },
  [types.SET_PAYMENT_METHOD](state, paymentMethod) {
    state.payment_method = paymentMethod;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
