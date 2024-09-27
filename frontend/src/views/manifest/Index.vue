<template>
  <div class="container mb-12">
    <transition name="fade-in" appear>
      <div>
        <div class="flex items-center mb-6 no-print">
          <div class="rr-field rr-field--select mb-0 mr-4">
            <select class="rr-field__input pr-8" v-model="selectedManifest">
              <option :value="null" disabled>{{ disabledOptionText }}</option>
              <option
                v-for="manifest in allManifests"
                :key="manifest.id"
                :value="manifest.id"
                v-text="manifest.manifest_name"
              ></option>
            </select>
          </div>
          <div class="rr-field mb-0 flex-1">
            <input
              class="rr-field__input"
              type="text"
              v-model="query"
              ref="query"
              placeholder="Search Manifest / ASIN"
            />
          </div>
        </div>
        <div>
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click="$refs.confirmArchiveModal.openModal()"
            v-if="selectedManifest"
          >
            Archive Manifest
          </button>
        </div>
      </div>
    </transition>
    <transition name="fade-in" mode="out-in" appear>
      <div
        class="p-8 flex justify-center items-center"
        style="height: 65vh"
        v-if="(manifestItems.length < 1 && loading) || manifestChange"
      >
        <span class="text-sm font-medium text-gray-900">
          Loading Manifest Items...
        </span>
      </div>
      <table
        class="rr-table min-w-full table-auto shadow-lg"
        v-if="manifestItems.length > 0"
      >
        <thead>
          <tr>
            <th class="rr-table__th">Item</th>
            <th class="rr-table__th text-right w-1/6">Price</th>
            <th class="rr-table__th w-1/6">ASIN</th>
            <th class="rr-table__th w-1/6">Added</th>
          </tr>
        </thead>
        <tbody class="bg-white">
          <tr
            class="rr-table__tr--hover"
            v-for="item in manifestItems"
            :key="item._id"
            @click="selectManifestItem(item)"
          >
            <td class="rr-table__td">
              <div class="flex flex-col">
                <div class="text-sm leading-5 font-medium text-gray-900">
                  {{ item.title | truncate(120) }}
                </div>
                <div class="text-xs leading-5 text-gray-500">
                  {{ item.upc }}
                </div>
              </div>
            </td>
            <td class="rr-table__td text-sm leading-5 text-gray-900 text-right">
              {{ formatCurrency(item.price) }}
              <div class="text-xs leading-5 text-gray-500" v-if="item.cost">
                Cost: {{ formatCurrency(item.cost) }}
              </div>
            </td>
            <td class="rr-table__td text-sm leading-5 text-gray-900">
              {{ item.asin || null }}
            </td>
            <td class="rr-table__td">
              <div class="text-sm leading-5 text-gray-900">
                {{ item.created_at | dateFromNow }}
              </div>
              <div class="text-xs leading-5 text-gray-500">
                {{ item.created_at | dateFormatted }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </transition>
    <manifest-upload ref="manifestUploadModal" />
    <ConfirmationModal
      ref="confirmArchiveModal"
      :header="`Are you sure you want to archive this manifest: ${
        allManifests.find((m) => m.id == selectedManifest).manifest_name
      }?`"
      body="You will not be able to access this manifest again."
      @response="(res) => (res ? archiveManifest() : null)"
      v-if="selectedManifest"
    />
  </div>
</template>

<script>
import moment from "moment";
import { formatCurrency } from "@/helpers";
import { mapGetters } from "vuex";
import _ from "lodash";
import ManifestUpload from "@/components/ManifestUpload.vue";
import ConfirmationModal from "@/components/ConfirmationModal.vue";

export default {
  name: "ManifestIndex",

  components: { ManifestUpload, ConfirmationModal },

  computed: {
    ...mapGetters(["manifest"]),
    disabledOptionText() {
      return this.allManifests.length == 0
        ? "No Manifests Created"
        : "Select a Manifest";
    },
  },

  data() {
    return {
      allManifests: [],
      selectedManifest: null,
      manifestItems: [],
      query: "",
      loading: false,
      manifestChange: false,
    };
  },

  watch: {
    query: _.debounce(function () {
      this.queryManifestItems();
    }, 500),
    selectedManifest(manifest) {
      this.manifestChange = true;
      this.queryManifestItems();
      this.$store.dispatch("setManifest", manifest);
    },
    manifestItems() {
      this.loading = false;
      this.$refs.query.focus();

      if (this.manifestChange) {
        this.manifestChange = false;
      }
    },
  },

  methods: {
    getManifests() {
      this.$store.dispatch("getManifests").then((manifests) => {
        this.allManifests = manifests;

        if (
          this.manifest &&
          this.allManifests.find((m) => m.id == this.manifest)
        ) {
          this.selectedManifest = this.manifest;
        }

        if (this.allManifests.length == 0) {
          this.$toasted.show(
            "No manifests exist you can try uploading one via Excel.",
            { type: "info" }
          );
          this.$refs.manifestUploadModal.openModal();
        }
      });
    },

    queryManifestItems() {
      this.loading = true;

      this.$store
        .dispatch("queryManifestItems", {
          manifestId: this.selectedManifest,
          query: this.query,
        })
        .then((items) => {
          this.manifestItems = items;
        });
    },

    selectManifestItem(item) {
      this.$router.push({
        name: "items.create",
        query: {
          upc: item.upc,
        },
        params: {
          item: item,
        },
      });
    },

    archiveManifest() {
      this.$store
        .dispatch("archiveManifest", this.selectedManifest)
        .then(() => {
          this.selectedManifest = null;
          this.manifestItems = [];
          this.getManifests();
        });
    },

    formatCurrency,
  },

  mounted() {
    this.getManifests();
  },

  filters: {
    dateFromNow(date) {
      return moment(date).fromNow();
    },

    dateFormatted(date) {
      return moment(date).format("MMMM Do, YYYY");
    },
  },

  metaInfo: {
    title: "Manifest / ",
  },
};
</script>
