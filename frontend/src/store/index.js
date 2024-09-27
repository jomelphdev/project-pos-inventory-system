import Vue from "vue";
import Vuex from "vuex";
import VuexPersistence from "vuex-persist";

import session from "@/store/modules/session";
import service from "@/store/modules/service";
import printing from "@/store/modules/printing";
import preferences from "@/store/modules/preferences";
import pos from "@/store/modules/pos";
import notifications from "@/store/modules/notifications";
import manifest from "@/store/modules/manifest";

import stripeService from "./api/stripe.service";
import usersService from "./api/users.service";
import adminsService from "./api/admins.service";
import itemsService from "./api/items.service";
import ordersService from "./api/orders.service";
import returnsService from "./api/returns.service";
import reportsService from "./api/reports.service";
import manifestsService from "./api/manifests.service";
import preferencesService from "./api/preferences.service";
import imagesService from "./api/images.service";
import verificationService from "./api/verification.service";
import organizationService from "./api/organization.service";
import cardProcessingService from "./api/card-processing.service";
import quickbooksService from "./api/quickbooks.service";
import giftCardsService from "./api/gift-cards.service";

Vue.use(Vuex);

const vuexLocal = new VuexPersistence({
  storage: window.localStorage,
  modules: [
    "session",
    "service",
    "printing",
    "preferences",
    "pos",
    "notifications",
    "manifest",
  ],
});

export default new Vuex.Store({
  modules: {
    session,
    service,
    printing,
    preferences,
    pos,
    notifications,
    manifest,

    stripeService,
    usersService,
    adminsService,
    itemsService,
    ordersService,
    returnsService,
    reportsService,
    manifestsService,
    preferencesService,
    imagesService,
    verificationService,
    organizationService,
    cardProcessingService,
    quickbooksService,
    giftCardsService,
  },
  plugins: [vuexLocal.plugin],
});
