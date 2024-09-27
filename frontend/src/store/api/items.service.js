// import * as types from "../mutations";
import Axios from "axios";
import { basicCatch } from "./api-helpers";

const state = {};

export const getters = {};

export const actions = {
  // ROUTES
  // POST
  createItem(context, item) {
    return new Promise((res, rej) => {
      Axios.post("/items/create", item)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.item);
          }
        })
        .catch((err) => {
          basicCatch(err, "There was an error creating this item.");
          rej();
        });
    });
  },

  updateItem(context, { itemId, update }) {
    return new Promise((res, rej) => {
      Axios.post(`/items/update/${itemId}`, update)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.item);
          }
        })
        .catch((err) => {
          basicCatch(err, "There was an error updating this item.");
          rej();
        });
    });
  },

  deleteItem(context, itemId) {
    return new Promise((res, rej) => {
      Axios.post(`/items/delete/${itemId}`)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body);
          }
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to delete item.");
          rej();
        });
    });
  },

  uploadItems(context, file) {
    let formData = new FormData();
    formData.append("inventory_file", file);

    return new Promise((res, rej) => {
      Axios.post("/items/import", formData)
        .then(() => {
          res();
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to import items.");
          rej();
        });
    });
  },

  calculatePrice(
    context,
    {
      price,
      classification_id = null,
      condition_id = null,
      discount_id = null,
      discount_amount = null,
    }
  ) {
    return new Promise((res, rej) => {
      Axios.post("/items/calculate-price", {
        price: price,
        classification_id: classification_id,
        condition_id: condition_id,
        discount_id: discount_id,
        discount_amount: discount_amount,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.price);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to calculate item price."
          );
          rej();
        });
    });
  },

  calculatePriceForMultipleItems(context, items) {
    return new Promise((res, rej) => {
      Axios.post("/items/list/calculate-price", { items: items })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.item_prices);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to calculate items prices."
          );
          rej();
        });
    });
  },

  queryItems(context, { query, last_seen_id = 0 }) {
    return new Promise((res, rej) => {
      Axios.post("/items/query", {
        query,
        last_seen_id,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.items);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            `There was an error with your search, please try again.`
          );
          rej();
        });
    });
  },

  getItemsCountForQuery(context, query) {
    return new Promise((res, rej) => {
      Axios.post("/items/query/count", { query: query })
        .then(({ data }) => {
          if (data.success) {
            res(data.data.items_count);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong checking for items related by title."
          );
          rej();
        });
    });
  },

  getItemBySku(context, sku) {
    return new Promise((res, rej) => {
      Axios.post("/items/query/sku", { sku: sku })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.item);
          }
        })
        .catch((err) => {
          const msg = err.data.message
            ? err.data.message
            : "Something went wrong while trying to fetch item.";

          basicCatch(err, msg);
          rej();
        });
    });
  },

  // Gets existing items via UPC.
  getItemsByUpc(context, { upc, options }) {
    return new Promise((res, rej) => {
      Axios.post("/items/query/upc", { upc: upc, options: options })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.items);
          }
        })
        .catch((err) => {
          const msg = err.data.message
            ? err.data.message
            : "Something went wrong while trying to fetch items.";

          basicCatch(err, msg);
          rej();
        });
    });
  },

  // Gets UPC data from UPC DB to create new items.
  getUpcData({ getters }, upc) {
    return new Promise((res, rej) => {
      Axios.post("/items/query/upc-data", {
        upc: upc,
        organization_id: getters.organization_id,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data);
          }
        })
        .catch((err) => {
          basicCatch(err, err.data.message);
          rej();
        });
    });
  },

  // GET
  getItem(context, itemId) {
    return new Promise((res, rej) => {
      Axios.get(`/items/${itemId}`)
        .then((response) => {
          const body = response.data;

          if (body.success) {
            res(body.data.item);
          }
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to fetch item.");
          rej();
        });
    });
  },

  getUsedConditionsFromTitle(context, title) {
    return new Promise((res, rej) => {
      Axios.get("/items/query/title-conditions", { params: { title: title } })
        .then(({ data }) => {
          if (data.success) {
            res(data.data.used_conditions);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to see what conditions are related to this items title."
          );
          rej();
        });
    });
  },

  calculateConsignmentFee(context, { consignor_id, price }) {
    return new Promise((res, rej) => {
      Axios.get("/items/calculate-consignment-fee", {
        params: { consignor_id, price },
      })
        .then(({ data }) => {
          res(data.data);
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to calculate consignment fee."
          );
          rej();
        });
    });
  },

  getItemHistory(context, itemId) {
    return new Promise((res, rej) => {
      Axios.get(`/items/history/${itemId}`)
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
