<template>
  <div>
    <h1 class="h1">Announcement Creation</h1>

    <div>
      <div class="rr-field">
        <label class="rr-field__label">
          Recipients

          <span
            class="rr-field__label-required"
            v-if="!$v.announcementForm.recipients.required"
          >
            Required
          </span>
        </label>

        <div
          class="rr-field__radio mr-4 inline-block"
          v-for="(role, index) in roles"
          :key="index"
        >
          <input
            type="checkbox"
            v-model="announcementForm.recipients"
            :id="`input-${index}`"
            :value="role"
            class="rr-field__radio-input"
          />
          <label
            :for="`input-${index}`"
            class="rr-field__radio-label items-baseline"
            >{{ capitalizeFirstLetter(role) }}s</label
          >
        </div>
        <div class="rr-field__radio mr-4 inline-block">
          <input
            type="checkbox"
            :id="`input-all`"
            :checked="allSelected"
            @click="toggleAllStores"
            class="rr-field__radio-input"
          />
          <label :for="`input-all`" class="rr-field__radio-label items-baseline"
            >All</label
          >
        </div>
      </div>

      <div>
        <label class="rr-field__label">Header</label>

        <quill-editor ref="quillHeader" v-model="announcementForm.header" />
      </div>

      <div>
        <label class="rr-field__label mt-4">
          Body

          <span
            class="rr-field__label-required"
            v-if="!$v.announcementForm.body.required"
          >
            Required
          </span>
        </label>

        <quill-editor ref="quillBody" v-model="announcementForm.body" />
      </div>

      <div>
        <label class="rr-field__label mt-4">Footer</label>

        <quill-editor ref="quillFooter" v-model="announcementForm.footer" />
      </div>

      <button
        class="rr-button rr-button--lg rr-button--primary-solid mt-4 inline-block"
        :disabled="$v.announcementForm.$invalid"
        @click="submitAnnouncement"
      >
        Create
      </button>
      <button
        class="rr-button rr-button--lg rr-button--primary mt-4 ml-4 inline-block"
        :disabled="$v.announcementForm.$invalid"
        @click="$refs.previewModal.openModal()"
      >
        Preview
      </button>
    </div>

    <NotificationModal
      ref="previewModal"
      :notification="announcementForm"
      :preview="true"
    />
  </div>
</template>

<script>
import { quillEditor } from "vue-quill-editor";
import { required } from "vuelidate/lib/validators";

import NotificationModal from "@/components/NotificationModal";

import Form from "@/classes/Form";

import { capitalizeFirstLetter } from "@/helpers";

export default {
  components: {
    quillEditor,
    NotificationModal
  },

  computed: {
    allSelected() {
      return this.roles.length == this.announcementForm.recipients.length;
    }
  },

  data() {
    return {
      roles: ["owner", "manager", "employee"],
      announcementForm: new Form({
        recipients: [],
        header: null,
        body: null,
        footer: null
      })
    };
  },

  methods: {
    toggleAllStores() {
      if (this.allSelected) return (this.announcementForm.recipients = []);
      this.announcementForm.recipients = this.roles;
    },

    submitAnnouncement() {
      this.$store
        .dispatch("createAnnouncement", this.announcementForm.data)
        .then(() => {
          this.$toasted.show("Successfully created announcement!", {
            type: "success"
          });
          this.announcementForm.reset();
        });
    },

    capitalizeFirstLetter
  },

  validations() {
    return {
      announcementForm: {
        recipients: { required },
        body: { required }
      }
    };
  }
};
</script>

<style></style>
