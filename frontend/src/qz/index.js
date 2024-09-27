import Vue from "vue";
import QzTray from "@/qz/qz-tray";
import QzTrayAction from "@/qz/qz-tray-action.vue";
import QzTrayConnect from "@/qz/qz-tray-connect.vue";
// import QzTrayFormElement from "@/qz/qz-tray-form-element.vue";
// import QzTrayFormNested from "@/qz/qz-tray-form-nested.vue";
import QzTrayInput from "@/qz/qz-tray-input.vue";
import QzTrayOptions from "@/qz/qz-tray-options.vue";
import QzTrayPrinters from "@/qz/qz-tray-printers.vue";
// import QzTrayPrintMode from "@/qz/qz-tray-print-mode.vue";
// import QzTrayInputIframe from "@/qz/qz-tray-input-iframe.vue";
import QzTrayHelp from "@/qz/qz-tray-help.vue";

const Components = {
  QzTray,
  QzTrayAction,
  QzTrayConnect,
  //   QzTrayFormElement,
  //   QzTrayFormNested,
  QzTrayInput,
  QzTrayOptions,
  QzTrayPrinters,
  //   QzTrayPrintMode,
  //   QzTrayInputIframe,
  QzTrayHelp
};

Object.keys(Components).forEach(name => {
  Vue.component(name, Components[name]);
});

export default Components;
