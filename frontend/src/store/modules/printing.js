import * as types from "@/store/mutations";
// import * as qz from "qz-tray";
// import { sha256 } from "js-sha256";

const state = {
  qzConnected: false,
  qzLabelPrinter: "",
  qzReceiptPrinter: "",
  qzPanel: false,
  wantsLabels: false,
  wantsLabelsConfirmation: true
};

export const getters = {
  qzConnected: printingState => printingState.qzConnected,
  qzLabelPrinter: printingState => printingState.qzLabelPrinter,
  qzReceiptPrinter: printingState => printingState.qzReceiptPrinter,
  qzReadyToPrint: printingState => {
    return (
      (!!printingState.qzLabelPrinter || !!printingState.qzReceiptPrinter) &&
      printingState.qzConnected
    );
  },
  qzPanel: printingState => printingState.qzPanel,
  wantsLabels: printingState => printingState.wantsLabels,
  wantsLabelsConfirmation: printingState =>
    printingState.wantsLabelsConfirmation
};

export const actions = {
  updateQzConnected({ commit }, newValue) {
    commit(types.UPDATE_QZ_CONNECTED, newValue);
  },
  updateQzPrinter({ commit }, newValue) {
    commit(types.UPDATE_QZ_PRINTER, newValue);
  },
  updateQzReceiptPrinter({ commit }, newValue) {
    commit(types.UPDATE_QZ_RECEIPT_PRINTER, newValue);
  },
  updateQzPanel({ commit }, newValue) {
    commit(types.UPDATE_QZ_PANEL, newValue);
  },
  setWantsLabels({ commit }, bool) {
    commit(types.SET_WANTS_LABELS, bool);
  },
  setWantsLabelsConfirmation({ commit }, bool) {
    commit(types.SET_WANTS_LABELS_CONFIRMATION, bool);
  }
};

export const mutations = {
  [types.UPDATE_QZ_CONNECTED](printingState, newValue) {
    printingState.qzConnected = newValue;
  },
  [types.UPDATE_QZ_PRINTER](printingState, newValue) {
    printingState.qzLabelPrinter = newValue;
  },
  [types.UPDATE_QZ_RECEIPT_PRINTER](printingState, newValue) {
    printingState.qzReceiptPrinter = newValue;
  },
  [types.UPDATE_QZ_PANEL](printingState, newValue) {
    printingState.qzPanel = newValue;
  },
  [types.SET_WANTS_LABELS](printingState, bool) {
    printingState.wantsLabels = bool;
  },
  [types.SET_WANTS_LABELS_CONFIRMATION](printingState, bool) {
    printingState.wantsLabelsConfirmation = bool;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
