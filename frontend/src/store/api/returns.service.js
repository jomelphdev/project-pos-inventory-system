import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {
  created_return: null
};

export const getters = {
  created_return: state => state.created_return
};

export const actions = {
  // ROUTES
  // POST
  createReturn(context, returnData) {
    return new Promise((res, rej) => {
      Axios.post("/returns/create", returnData)
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.data.return);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to create your return."
          );
          rej();
        });
    });
  },

  calculateRefund(context, { orderId, items }) {
    return new Promise((res, rej) => {
      Axios.post("/returns/calculate-refund", {
        items: items,
        pos_order_id: orderId
      })
        .then(response => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch(err => {
          basicCatch(
            err,
            "Something went wrong while trying to calculate refund."
          );
          rej();
        });
    });
  }
};

export const mutations = {
  [types.SET_CREATED_RETURN](state, returnData) {
    state.created_return = returnData;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
