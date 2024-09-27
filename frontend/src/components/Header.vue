<template>
  <div class="rr-nav tracking-tighter" @click="hideUserMenu">
    <div class="container">
      <div class="flex items-center px-6 py-8" data-test="appHeader">
        <div class="flex items-center">
          <span class="font-bold italic text-2xl mr-1">
            <a :href="linkTo">
              <div
                class="svg svg-logo-main"
                style="height: 40px; width: 150px"
              />
            </a>
          </span>
          <a
            href="https://help.retailright.app/#/changelog"
            target="_blank"
            class="mr-8 text-xs hover:underline"
            v-text="version ? `v${version}` : ''"
          />
        </div>
        <template v-if="loggedIn && verified">
          <router-link
            :to="{ name: 'scan' }"
            class="mr-4"
            data-test="scan-nav"
            v-if="userPermissions.find((p) => p == 'scan')"
            >Scan</router-link
          >
          <router-link
            :to="{ name: 'items.index' }"
            class="mr-4"
            data-test="items-nav"
            v-if="userPermissions.find((p) => p.includes('items'))"
            >Items</router-link
          >
          <template v-if="onItems && userPermissions.includes('import')">
            <span class="-ml-2 mr-2">|</span>
            <div
              class="mr-4 cursor-pointer"
              data-test="item-import"
              @click="$refs.itemsUploadModal.openModal()"
            >
              Import
            </div>
          </template>
          <router-link
            :to="{ name: 'manifest.index' }"
            class="mr-4"
            v-if="userPermissions.find((p) => p == 'scan')"
            >Manifest</router-link
          >
          <template v-if="onManifest && userPermissions.includes('import')">
            <span class="-ml-2 mr-2">|</span>
            <div
              class="mr-4 cursor-pointer"
              @click="$refs.manifestUploadModal.openModal()"
            >
              Upload
            </div>
          </template>
          <router-link
            :to="{ name: 'reports.index' }"
            class="mr-4"
            v-if="userPermissions.find((p) => p.includes('reports'))"
            >Reports</router-link
          >
          <router-link
            :to="{ name: 'pos.index' }"
            class="mr-4"
            v-if="userPermissions.find((p) => p.includes('pos'))"
            >POS</router-link
          >
          <template v-if="onPos">
            <span class="-ml-2 mr-2">|</span>
            <router-link :to="{ name: 'pos.orders' }" class="mr-4"
              >Orders</router-link
            >
            <router-link :to="{ name: 'pos.returns' }" class="mr-4"
              >Returns</router-link
            >
            <router-link
              :to="{ name: 'pos.gift-cards' }"
              class="mr-4"
              v-if="userRole && ['owner', 'manager'].includes(userRole.name)"
            >
              Gift Cards
            </router-link>
          </template>
        </template>
        <span class="ml-auto relative">
          <template v-if="loggedIn && verified">
            <span
              class="mr-4 cursor-pointer"
              @click="togglePrintSettings"
              :class="{ 'font-bold': qzPanel }"
              data-test="printSettings-toolbar"
            >
              <PrinterIcon
                size="18"
                class="inline-block mr-1"
                :class="{
                  'text-red-700': !labelPrinterReady || !receiptPrinterReady,
                  'text-green-600': labelPrinterReady && receiptPrinterReady,
                }"
              />

              <span
                class="rr-pill rr-pill--default"
                :class="{ 'border-red-700': !labelPrinterReady }"
              >
                Label → {{ labelPrinter | truncate(16) }}
              </span>
              <span
                class="rr-pill rr-pill--default ml-1"
                :class="{ 'border-red-700': !receiptPrinterReady }"
              >
                Receipt → {{ receiptPrinter | truncate(16) }}
              </span>
            </span>
          </template>
          <template v-if="loggedIn">
            <a
              href="#"
              @click.prevent.stop="toggleUserMenu = !toggleUserMenu"
              :class="{ 'opacity-50': toggleUserMenu }"
              data-test="user-menu-icon"
              >{{ currentUser.full_name }}
              <MenuIcon
                size="18"
                class="inline-block ml-1"
                style="margin-bottom: 2px"
              />
            </a>
          </template>
          <transition name="fade-in">
            <div
              class="border-gray-200 absolute top-0 right-0 px-4 py-4 mb-8 -ml-4 origin-bottom-left bg-white border rounded-md shadow-md flex flex-col space-y-2 z-50"
              style="top: 2rem; min-width: 8rem"
              v-show="toggleUserMenu"
              @click="hideUserMenu"
            >
              <router-link :to="{ name: 'account' }" class="mr-4"
                >Profile</router-link
              >
              <router-link
                :to="{ name: 'preferences' }"
                class="mr-4"
                v-if="userRole && ['owner', 'manager'].includes(userRole.name)"
                >Settings</router-link
              >
              <a class="cursor-pointer" @click="routeWebsite">Website</a>
              <a href="https://help.retailright.app" target="_blank"
                >Help Center</a
              >
              <a
                href="https://www.youtube.com/channel/UC05FNjdlVufssB0Ni8Grr9Q"
                target="_blank"
                >YouTube Tutorials</a
              >
              <template v-if="loggedIn">
                <a href="#" @click.prevent="logout" data-test="user-menu-logout"
                  >Logout</a
                >
              </template>
              <template v-else>
                <router-link to="/login">Login</router-link>
              </template>
            </div>
          </transition>
        </span>
      </div>
    </div>
    <ManifestUpload ref="manifestUploadModal" />
    <ItemUpload ref="itemsUploadModal" />
  </div>
</template>

<script>
import { mapGetters } from "vuex";

import ManifestUpload from "@/components/ManifestUpload";
import ItemUpload from "@/components/ItemUpload";
import { PrinterIcon, MenuIcon } from "@vue-hero-icons/outline";

export default {
  components: { ManifestUpload, ItemUpload, PrinterIcon, MenuIcon },

  data() {
    return {
      toggleUserMenu: false,
    };
  },

  computed: {
    ...mapGetters([
      "loggedIn",
      "currentUser",
      "userPermissions",
      "userRole",
      "qzPanel",
      "qzLabelPrinter",
      "qzReceiptPrinter",
      "qzReadyToPrint",
      "verified",
      "version",
      "organization",
    ]),
    labelPrinterReady() {
      return this.qzLabelPrinter && this.qzReadyToPrint;
    },
    labelPrinter() {
      return this.labelPrinterReady ? this.qzLabelPrinter : "Not Ready";
    },
    receiptPrinterReady() {
      return this.qzReceiptPrinter && this.qzReadyToPrint;
    },
    receiptPrinter() {
      return this.receiptPrinterReady ? this.qzReceiptPrinter : "Not Ready";
    },
    onItems() {
      return this.$route.fullPath.includes("/items");
    },
    onPos() {
      return this.$route.fullPath.includes("/pos");
    },
    onManifest() {
      return this.$route.fullPath.includes("/manifest");
    },
    siteUrl() {
      if (this.hasSlug) {
        return `${process.env.VUE_APP_ONLINE_SITE_URL}${this.organization.slug}`;
      }

      return null;
    },
    hasSlug() {
      return this.organization && this.organization.slug;
    },
    linkTo() {
      if (this.$route.name == "create")
        return process.env.VUE_APP_MARKETING_URL;
      return "/";
    },
  },

  methods: {
    logout() {
      this.$store.dispatch("logout");
    },
    togglePrintSettings() {
      this.$store.dispatch("updateQzPanel", !this.qzPanel);
    },
    hideUserMenu() {
      this.toggleUserMenu = false;
    },
    routeWebsite() {
      if (!this.hasSlug) {
        return this.$router.push({ name: "preferences.site" });
      }

      window.open(this.siteUrl, "_blank");
    },
  },
};
</script>
