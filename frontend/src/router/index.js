import Vue from "vue";
import VueRouter from "vue-router";
import store from "@/store";

if (!process.env || process.env.NODE_ENV !== "test") {
  Vue.use(VueRouter);
}

const routes = [
  {
    path: "/",
    name: "home",
    redirect: { name: "scan" },
    // component: () => import("@/views/Home"),
  },
  {
    path: "/scan",
    name: "scan",
    component: () => import(/* webpackChunkName: "scan" */ "@/views/Scan"),
  },
  {
    path: "/items",
    component: () => import(/* webpackChunkName: "items" */ "@/views/Items"),
    children: [
      {
        path: "",
        name: "items.index",
        component: () =>
          import(/* webpackChunkName: "itemsIndex" */ "@/views/items/Index"),
      },
      {
        path: "/items/create",
        name: "items.create",
        component: () =>
          import(/* webpackChunkName: "itemsCreate" */ "@/views/items/Create"),
        props: (route) => ({
          upc: route.query.upc,
        }),
      },
      {
        path: "/items/:id/edit",
        name: "items.edit",
        props: true,
        component: () =>
          import(/* webpackChunkName: "itemsEdit" */ "@/views/items/Edit"),
      },
    ],
  },
  {
    path: "/manifest",
    component: () =>
      import(/* webpackChunkName: "manifest" */ "@/views/Manifest"),
    children: [
      {
        path: "",
        name: "manifest.index",
        component: () =>
          import(
            /* webpackChunkName: "manifestIndex" */ "@/views/manifest/Index"
          ),
      },
    ],
  },
  {
    path: "/reports",
    name: "reports",
    redirect: { name: "reports.daily-sales" },
    component: () =>
      import(/* webpackChunkName: "reports" */ "@/views/Reports"),
    children: [
      {
        path: "",
        name: "reports.index",
        redirect: { name: "reports.daily-sales" },
        component: () =>
          import(
            /* webpackChunkName: "reportsIndex" */ "@/views/reports/Index"
          ),
      },
      {
        path: "daily-sales",
        name: "reports.daily-sales",
        component: () =>
          import(
            /* webpackChunkName: "reportsDailySales" */ "@/views/reports/DailySales"
          ),
      },
      {
        path: "sales-report",
        name: "reports.sales-report",
        component: () =>
          import(
            /* webpackChunkName: "reportsSalesReport" */ "@/views/reports/SalesReport"
          ),
      },
      {
        path: "item-sales",
        name: "reports.item-sales",
        component: () =>
          import(
            /* webpackChunkName: "reportsItemsSales" */ "@/views/reports/ItemSales"
          ),
      },
      {
        path: "inventory",
        name: "reports.inventory",
        component: () =>
          import(
            /* webpackChunkName: "reportsInventory" */ "@/views/reports/Inventory"
          ),
      },
      {
        path: "drawers",
        name: "reports.drawers",
        component: () =>
          import(
            /* webpackChunkName: "reportsDrawers" */ "@/views/reports/Drawers"
          ),
      },
      {
        path: "consignment",
        name: "reports.consignment",
        component: () =>
          import(
            /* webpackChunkName: "reportsConsignment" */ "@/views/reports/Consignment"
          ),
      },
      {
        path: "consignment-invoices",
        name: "reports.consignment-invoices",
        component: () =>
          import(
            /* webpackChunkName: "reportsConsignmentInvoices" */ "@/views/reports/ConsignmentInvoices"
          ),
      },
      {
        path: "quickbooks",
        name: "reports.quickbooks",
        component: () =>
          import(
            /* webpackChunkName: "reportsInventory" */ "@/views/reports/QuickBooksReport"
          ),
      },
      {
        path: "gift-card",
        name: "reports.gift-card",
        component: () =>
          import(
            /* webpackChunkName: "reportsGiftCard" */ "@/views/reports/GiftCard"
          ),
      },
    ],
  },
  {
    path: "/pos",
    component: () => import(/* webpackChunkName: "pos" */ "@/views/Pos"),
    children: [
      {
        path: "",
        name: "pos.index",
        component: () =>
          import(/* webpackChunkName: "posIndex" */ "@/views/pos/Index"),
      },
      {
        path: "returns",
        name: "pos.returns",
        props: true,
        component: () =>
          import(/* webpackChunkName: "posReturns" */ "@/views/pos/Returns"),
      },
      {
        path: "orders",
        name: "pos.orders",
        props: true,
        component: () =>
          import(/* webpackChunkName: "ordersIndex" */ "@/views/orders/Index"),
      },
      {
        path: "orders/:id",
        name: "pos.orders.details",
        props: true,
        component: () =>
          import(
            /* webpackChunkName: "ordersIndex" */ "@/views/orders/Details"
          ),
      },
      {
        path: "gift-cards",
        name: "pos.gift-cards",
        props: true,
        component: () =>
          import(
            /* webpackChunkName: "posGiftCards" */ "@/views/pos/GiftCards"
          ),
      },
      {
        path: "gift-cards/:id/edit",
        name: "pos.gift-cards.edit",
        props: true,
        component: () =>
          import(
            /* webpackChunkName: "posGiftCardsEdit" */ "@/views/pos/GiftCardsEdit"
          ),
      },
    ],
  },
  {
    path: "/login",
    name: "login",
    component: () =>
      import(/* webpackChunkName: "login" */ "@/views/auth/Login"),
  },
  {
    path: "/verify",
    name: "verify",
    component: () =>
      import(/* webpackChunkName: "verify" */ "@/views/auth/Verify"),
  },
  {
    path: "/create",
    name: "create",
    component: () =>
      import(/* webpackChunkName: "create" */ "@/views/auth/Create"),
  },
  {
    path: "/admin",
    name: "admin",
    redirect: { name: "admin.announcements" },
    component: () => import(/* webpackChunkName: "admin" */ "@/views/Admin"),
    children: [
      {
        path: "announcements",
        name: "admin.announcements",
        component: () =>
          import(
            /* webpackChunkName: "adminAnnouncements" */ "@/views/admin/Announcements"
          ),
      },
    ],
  },
  {
    path: "/settings",
    name: "preferences",
    redirect: { name: "preferences.stores" },
    component: () =>
      import(/* webpackChunkName: "preferences" */ "@/views/Preferences"),
    children: [
      {
        path: "",
        name: "preferences.index",
        redirect: { name: "preferences.stores" },
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesIndex" */ "@/views/preferences/Index"
          ),
      },
      {
        path: "classifications",
        name: "preferences.classifications",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesClassifications" */ "@/views/preferences/Classifications"
          ),
      },
      {
        path: "conditions",
        name: "preferences.conditions",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesConditions" */ "@/views/preferences/Conditions"
          ),
      },
      {
        path: "discounts",
        name: "preferences.discounts",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesDiscounts" */ "@/views/preferences/Discounts"
          ),
      },
      {
        path: "stations",
        name: "preferences.checkoutStations",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesCheckoutStations" */ "@/views/preferences/CheckoutStations"
          ),
      },
      {
        path: "stores",
        name: "preferences.stores",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesStores" */ "@/views/preferences/Stores"
          ),
      },
      {
        path: "employees",
        name: "preferences.employees",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesEmployees" */ "@/views/preferences/Employees"
          ),
      },
      {
        path: "consignors",
        name: "preferences.consignors",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesConsignors" */ "@/views/preferences/Consignors"
          ),
      },
      {
        path: "online-site",
        name: "preferences.site",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesSite" */ "@/views/preferences/Site"
          ),
      },
      {
        path: "pos",
        name: "preferences.pos",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesPos" */ "@/views/preferences/Pos"
          ),
      },
      {
        path: "billing",
        name: "preferences.billing",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesBilling" */ "@/views/preferences/Billing"
          ),
      },
      {
        path: "quickbooks",
        name: "preferences.quickbooks",
        meta: { requiresAdmin: true },
        component: () =>
          import(
            /* webpackChunkName: "preferencesQuickBooks" */ "@/views/preferences/QuickBooks"
          ),
      },
    ],
  },
  {
    path: "/account",
    name: "account",
    redirect: { name: "account.profile" },
    component: () =>
      import(/* webpackChunkName: "account" */ "@/views/Account"),
    children: [
      {
        path: "",
        name: "account.index",
        redirect: { name: "account.profile" },
        component: () =>
          import(
            /* webpackChunkName: "accountIndex" */ "@/views/account/Index"
          ),
      },
      {
        path: "profile",
        name: "account.profile",
        component: () =>
          import(
            /* webpackChunkName: "accountProfile" */ "@/views/account/Profile"
          ),
      },
      {
        path: "password",
        name: "account.password",
        component: () =>
          import(
            /* webpackChunkName: "accountPassword" */ "@/views/account/Password"
          ),
      },
    ],
  },
  {
    path: "/quickbooks-redirect",
    name: "quickbooks.redirect",
    component: () =>
      import(
        /* webpackChunkName: "accountPassword" */ "@/views/QuickBooksRedirect"
      ),
  },
  {
    path: "*",
    name: "404",
    redirect: { name: "home" },
    // component: () => import("@/views/NotFound"),
  },
];

const router = new VueRouter({
  mode: "history",
  base: process.env.BASE_URL,
  routes,
});

router.beforeEach((to, from, next) => {
  const publicPages = store.getters.publicPages;
  const userPermissions = store.getters.userPermissions
    ? publicPages.concat(store.getters.userPermissions)
    : publicPages;

  const pageInPermissions = userPermissions.includes(to.name);
  const authRequired = !publicPages.includes(to.name);
  const notLoggedIn = !store.getters.loggedIn;
  const notVerified =
    store.getters.loggedIn && !store.getters.verified && to.name !== "verify";

  if (authRequired && notLoggedIn) {
    next({
      name: "login",
      query: { redirect: to.fullPath },
    });
  } else if (notVerified && to.name != "account.profile") {
    next({ name: "verify" });
  } else if (
    !notLoggedIn &&
    store.getters.subscriptionRequired &&
    to.name != "preferences.billing"
  ) {
    const needNewPaymentMethod = store.getters.updatedPaymentMethodRequired;
    let msg = needNewPaymentMethod
      ? "Payment method did not work, it is likely expired please update it and try again."
      : "Subscription has expired, please select a plan to continue usage.";

    if (!userPermissions.includes("preferences.billing")) {
      msg = needNewPaymentMethod
        ? "Payment method did not work, it is likely expired please notify employer to update it and try again."
        : "Subscription has expired. Please notify employer to continue usage.";

      setTimeout(() => {
        store.dispatch("logout");
      }, 3000);
    } else {
      next({ name: "preferences.billing" });
    }

    store.dispatch("pushNotifications", {
      text: msg,
      type: "info",
    });
  } else if (!pageInPermissions) {
    if (to.name == "quickbooks.redirect") {
      return next();
    }

    next({ name: "scan" });
    store.dispatch("pushNotifications", {
      text: "Access is blocked to this page.",
      type: "error",
    });
  } else if (to.matched.some((record) => record.meta.requiresAdmin)) {
    if (["owner", "manager"].includes(store.getters.userRole.name)) {
      return next();
    }

    next({
      name: "home",
      query: { redirect: to.fullPath },
    });
    store.dispatch("pushNotifications", {
      text: "Access is blocked to this page.",
      type: "error",
    });
  } else {
    next();
  }
});

export default router;
