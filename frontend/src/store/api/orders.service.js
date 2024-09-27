// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {
  orders_query_page: (state) => state.orders_query_page,
  queried_orders: (state) => state.queried_orders,
};

export const actions = {
  // ROUTES
  // POST
  createOrder(context, order) {
    return new Promise((res, rej) => {
      Axios.post("/orders/create", order)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.order);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to create your order."
          );
          rej();
        });
    });
  },

  calculateOrderTotals(
    { getters },
    { items, is_ebt = false, is_taxed = true, discount_amount = 0 }
  ) {
    return new Promise((res, rej) => {
      Axios.post("/orders/calculate-totals", {
        items: items,
        store_id: getters.posStore.id,
        ebt_order: is_ebt,
        is_taxed,
        discount_amount,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch((err) => {
          basicCatch(err, "Error while trying to calculate order totals.");
          rej();
        });
    });
  },

  calculatePosPayment(
    context,
    { total, cash = null, card = null, ebt = null, gc = null }
  ) {
    return new Promise((res, rej) => {
      Axios.post("/orders/calculate-payment", {
        cash: cash,
        card: card,
        ebt: ebt,
        gc: gc,
        total: total,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to calculate customers payment."
          );
          rej();
        });
    });
  },

  getOrders(root, { last_seen_id, query, date_to, date_from }) {
    return new Promise((res, rej) => {
      Axios.get(`/orders/organization`, {
        params: {
          last_seen_id,
          query,
          date_to,
          date_from,
        },
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.orders);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get your orders."
          );
          rej();
        });
    });
  },

  // GET
  getOrder(context, orderId) {
    return new Promise((res, rej) => {
      Axios.get(`/orders/${orderId}`)
        .then(async (response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.order);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get Order data."
          );
          rej();
        });
    });
  },

  getOrderForReturn(context, orderId) {
    return new Promise((res, rej) => {
      Axios.get(`/orders/return/${orderId}`)
        .then(({ data }) => {
          res(data.data.order);
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get Order data."
          );
          rej();
        });
    });
  },

  getOrderByMongoId(context, mongoId) {
    return new Promise((res, rej) => {
      Axios.get(`/orders/v2/${mongoId}`)
        .then(({ data }) => {
          if (data.success) {
            res(data.data.order);
          }
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to get V2 order");
          rej();
        });
    });
  },

  // get order promotional history logs
  getPromotionalLogs(context, itemId) {
    return new Promise((res, rej) => {
      Axios.get(`/orders/promotional-logs/${itemId}`)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to fetch item.");
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
