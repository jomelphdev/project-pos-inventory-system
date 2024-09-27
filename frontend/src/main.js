import Vue from "vue";
import App from "@/App.vue";
import store from "@/store";
import router from "@/router";
import Vue2Filters from "vue2-filters";
import Vuelidate from "vuelidate";
import VueCurrencyInput from "vue-currency-input";
import Toasted from "vue-toasted";
import "@/assets/tailwind.css";
import "@/qz";
import * as Sentry from "@sentry/browser";
import { Vue as VueIntegration } from "@sentry/integrations";
import { Integrations } from "@sentry/tracing";
import { VueMaskDirective } from "v-mask";
import VueMeta from "vue-meta";
import VCalendar from "v-calendar";
import { configureApi } from "@/store/api/index";
import VueIntercom from "@mathieustan/vue-intercom";
import VueQRCodeComponent from "vue-qrcode-component";
Vue.component("qr-code", VueQRCodeComponent);

if (process.env.NODE_ENV == "production") {
  Sentry.init({
    dsn: "https://1899ccba387d4506b7ba555cb016060e@o267043.ingest.sentry.io/5448187",
    integrations: [
      new VueIntegration({
        Vue,
        tracing: true,
      }),
      new Integrations.BrowserTracing(),
    ],
    tracesSampleRate: 1.0,
  });
}

Vue.use(Vue2Filters);
Vue.use(Vuelidate);
Vue.use(VueCurrencyInput, {
  globalOptions: {
    currency: "USD",
    locale: "en",
    valueAsInteger: true,
  },
});
Vue.use(Toasted, {
  theme: "toasted-primary",
  position: "top-left",
  duration: 3000,
  fullWidth: true,
});
Vue.use(VueMeta);
Vue.use(VCalendar);
Vue.use(VueIntercom, { appId: "cypwdlhe" });
Vue.directive("mask", VueMaskDirective);

Vue.config.productionTip = false;
Vue.prototype.console = console;

const app = new Vue({
  store,
  router,
  render: (h) => h(App),
}).$mount("#app");

if (window.Cypress) {
  // only available during cypress tests
  window.app = app;
}
window.getters = app.$store.getters;
window.$root = app.$root;
window.$toasted = app.$toasted;

configureApi();
