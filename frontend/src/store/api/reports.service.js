import * as types from "../mutations";
import Axios from "axios";
import { basicCatch, handleReportResponse } from "./api-helpers";
import moment from "moment";

const state = {
  daily_sales_data: null,
  sales_data: null,
  loading_report: null,
};

export const getters = {
  daily_sales_data: (state) => state.daily_sales_data,
  sales_data: (state) => state.sales_data,
  loading_report: (state) => state.loading_report,
};

const headers = Object.assign(
  {
    responseType: "blob",
  },
  Axios.defaults.headers
);

export const actions = {
  // ROUTES
  // POST
  getDailySalesReport({ dispatch }, { store_id, date, for_stations }) {
    dispatch("setLoadingReport", true);
    const filename = `Daily_Sales_${moment(date).format("MM-DD-YYYY")}`;

    Axios.post(
      `reports/daily-sales`,
      {
        store_id,
        date,
        for_stations,
      },
      headers
    )
      .then((response) => {
        handleReportResponse(response, filename);
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to generate report."
        );
      });
  },

  getDailySalesReportData({ dispatch }, { storeId, date, options = null }) {
    return new Promise((res, rej) => {
      Axios.post("reports/data/daily-sales", {
        store_id: storeId,
        date: date,
      })
        .then((response) => {
          const body = response.data;

          if (body.success) {
            if (!options || !options.hideMessages) {
              dispatch("pushNotifications", {
                text: "Loaded!",
                type: "success",
              });
            }

            res(body.data);
          }
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get sales data."
          );
          rej();
        });
    });
  },

  getSalesReport({ dispatch }, { storeIds, startDate, endDate }) {
    dispatch("setLoadingReport", true);
    const filename = `Sales_${moment(startDate).format(
      "MM-DD-YYYY"
    )}_to_${moment(endDate).format("MM-DD-YYYY")}`;

    Axios.post(
      `reports/sales`,
      {
        stores: storeIds,
        start_date: startDate,
        end_date: endDate,
      },
      headers
    )
      .then((response) => {
        handleReportResponse(response, filename);
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to generate report."
        );
      });
  },

  getSalesReportData(root, { storeIds, startDate, endDate }) {
    return new Promise((res, rej) => {
      Axios.post("reports/data/sales", {
        stores: storeIds,
        start_date: startDate,
        end_date: endDate,
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
            "Something went wrong while trying to get sales data."
          );
          rej();
        });
    });
  },

  getItemSalesReport(context, { start_date, end_date }) {
    Axios.post(
      "reports/item-sales",
      {
        start_date,
        end_date,
      },
      headers
    )
      .then((response) => {
        const filename = `Item_Sales_${moment(start_date).format(
          "MM-DD-YYYY"
        )}_to_${moment(end_date).format("MM-DD-YYYY")}`;
        handleReportResponse(response, filename);
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to generate report."
        );
      });
  },

  getInventoryReport({ dispatch }, { stores, with_empty_quantities }) {
    dispatch("setLoadingReport", true);
    const filename = `Inventory_Report_${moment().format("MM-DD-YYYY")}`;

    Axios.post(
      `reports/inventory`,
      {
        stores,
        with_empty_quantities,
      },
      headers
    )
      .then((response) => {
        handleReportResponse(response, filename);
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to generate report."
        );
      });
  },

  createConsignmentInvoice({ dispatch }, consignorId) {
    return new Promise((res, rej) => {
      Axios.post("reports/consignment-invoice", { consignor_id: consignorId })
        .then(() => {
          dispatch("pushNotifications", {
            text: "Invoice successfully created!",
            type: "success",
          });
          res();
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to create invoice."
          );
          rej();
        });
    });
  },

  setNewDrawerBalance(
    { dispatch },
    { checkout_station_id, actual_difference, new_balance }
  ) {
    return new Promise((res, rej) => {
      Axios.post("reports/drawer-balance", {
        checkout_station_id,
        actual_difference,
        new_balance,
      })
        .then(() => {
          dispatch("pushNotifications", {
            type: "success",
            text: "Successfully set balance!",
          });
          res();
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to set new balance."
          );
          rej();
        });
    });
  },

  deleteReport({ dispatch }, { id }) {
    return new Promise((res, rej) => {
      Axios.delete(`reports/${id}`)
        .then(() => {
          dispatch("pushNotifications", {
            type: "success",
            text: "Successfully deleted report.",
          });
          res();
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to delete report."
          );
          rej();
        });
    });
  },

  regenerateReport({ dispatch }, { id }) {
    Axios.post(`reports/${id}/regenerate`)
      .then((response) => {
        dispatch("pushNotifications", {
          type: "success",
          text: response.data.message,
        });
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to regenerate report."
        );
      });
  },

  getGiftCardReportData(context, { startDate, endDate }) {
    return new Promise((res, rej) => {
      Axios.post("reports/data/gift-card-report", {
        start_date: startDate,
        end_date: endDate,
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
            "Something went wrong while trying to get gift card data."
          );
          rej();
        });
    });
  },

  // GET
  getReportDirectories(context, { report_type, store_id }) {
    return new Promise((res, rej) => {
      Axios.get("reports/directories", {
        params: {
          report_type,
          store_id,
        },
      })
        .then(({ data }) => {
          res(data.data);
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to fetch directories."
          );
          rej();
        });
    });
  },

  downloadReport(context, { path, filename }) {
    Axios.get("reports/download", Object.assign({ params: { path } }, headers))
      .then((response) => {
        handleReportResponse(response, filename);
      })
      .catch((err) => {
        basicCatch(
          err,
          "Something went wrong while trying to download report."
        );
      });
  },

  getConsignmentReportData() {
    return new Promise((res, rej) => {
      Axios.get("reports/data/consignment")
        .then(({ data }) => {
          res(data.data);
        })
        .catch((err) => {
          basicCatch(err, "Something went wrong while trying to get data.");
          rej();
        });
    });
  },

  getConsignmentInvoices() {
    return new Promise((res, rej) => {
      Axios.get("reports/consignment-invoices")
        .then(({ data }) => {
          res(data.data);
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to retrieve invoices."
          );
          rej();
        });
    });
  },

  getCashDrawersReport() {
    return new Promise((res, rej) => {
      Axios.get("reports/data/drawers")
        .then(({ data }) => {
          res(data.data);
        })
        .catch((err) => {
          basicCatch(
            err,
            "Something went wrong while trying to get cash drawers reports."
          );
          rej();
        });
    });
  },

  // SETTERS
  setLoadingReport({ commit }, isLoading) {
    commit(types.SET_LOADING_REPORT, isLoading);
  },
};

export const mutations = {
  [types.SET_LOADING_REPORT](state, isLoading) {
    state.loading_report = isLoading;
  },
};

export default {
  state,
  getters,
  actions,
  mutations,
};
