// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  createGiftCard(context, giftCard) {
    return new Promise((res, rej) => {
      Axios.post(`/gift/gift-card`, giftCard)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch((err) => {
          rej(err);
        });
    });
  },

  // GET
  getGiftCards() {
    return new Promise((res, rej) => {
      Axios.get(`/gift/gift-cards`)
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

  checkGiftCardBalance(context, giftCard) {
    return new Promise((res, rej) => {
      Axios.post(`/gift/gift-card-check-balance/`, giftCard)
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

  getGiftCardTopUp(context, giftCardId) {
    return new Promise((res, rej) => {
      Axios.get(`/gift/top-up/${giftCardId}`)
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

  // PUT
  updateGiftCard(context, gift) {
    return new Promise((res, rej) => {
      Axios.put(`/gift/gift-card/${gift.id}`, gift.giftCard)
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

  activateDeactivate(context, gift) {
    return new Promise((res, rej) => {
      Axios.put(`/gift/activate-deactivate/${gift.id}`, {
        is_activated: gift.is_activated,
      })
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

  updateGiftCardBalance(context, gift) {
    return new Promise((res, rej) => {
      Axios.put(`/gift/update-gift-card-balance/${gift.giftId}`, gift)
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
