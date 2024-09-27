<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1">POS Settings</h1>
    </div>

    <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
      <div>
        <div class="rr-field__radio mb-6">
          <input
            type="checkbox"
            v-model="preferenceForm.hide_pos_sales"
            :id="'inputHideSlaes'"
            class="rr-field__radio-input"
            data-test="hideSales-input"
            @click="
              preferenceForm.hide_pos_sales = !preferenceForm.hide_pos_sales
            "
          />
          <label
            :for="'inputHideSlaes'"
            class="rr-field__radio-label items-baseline"
          >
            Hide Sales In POS
          </label>
        </div>

        <div class="rr-field__radio mb-6">
          <input
            type="checkbox"
            v-model="preferenceForm.using_merchant_partner"
            :id="'inputProcessor'"
            class="rr-field__radio-input"
            data-test="paymentPartner-input"
            @click="
              preferenceForm.using_merchant_partner = !preferenceForm.using_merchant_partner
            "
          />
          <label
            :for="'inputProcessor'"
            class="rr-field__radio-label items-baseline"
          >
            Use RetailRight's Payment Processor
          </label>
        </div>

        <div v-if="!preferenceForm.using_merchant_partner">
          <span class="font-bold">To Become A Merchant</span><br />
          <span>
            <a
              href="#"
              class="rr-link-blue underline"
              @click="requestApplication"
              >Request</a
            >
            and fill out the application.
          </span>

          <div class="mt-4">
            <a
              href="https://www.youtube.com/watch?v=18Vt8CpCn5A"
              class="rr-link-blue"
              target="_blank"
              >Watch Tutorial</a
            ><br />
          </div>
        </div>

        <div v-if="preferenceForm.using_merchant_partner">
          <div class="rr-field">
            <label class="rr-field__label">
              Merchant Username

              <span
                class="rr-field__label-required"
                data-test="required-indicator"
                v-if="!$v.preferenceForm.merchant_username.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="merchantUsername-input"
              v-model="preferenceForm.merchant_username"
              @input="$v.preferenceForm.merchant_username.$touch()"
            />
          </div>

          <div class="rr-field">
            <label class="rr-field__label">
              Merchant Password

              <span
                class="rr-field__label-required"
                data-test="required-indicator"
                v-if="!$v.preferenceForm.merchant_password.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="password"
              data-test="merchantPassword-input"
              v-model="preferenceForm.merchant_password"
              @input="$v.preferenceForm.merchant_password.$touch()"
            />
          </div>

          <div
            class="rr-field"
            v-if="
              preferenceForm.merchant_username &&
                preferenceForm.merchant_password
            "
          >
            <label class="rr-field__label">
              Merchant ID

              <span
                class="rr-field__label-required"
                data-test="required-indicator"
                v-if="!$v.preferenceForm.merchant_id.required"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                data-test="length-indicator"
                v-if="
                  preferenceForm.merchant_id &&
                    !$v.preferenceForm.merchant_id.minLength
                "
              >
                MID's are atleast 12 characters
              </span>
              <span
                class="rr-field__label-required"
                data-test="length-indicator"
                v-if="!merchantVerified && !verifying && initialRequestSent"
              >
                Not a verified MID
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="merchantId-input"
              v-model="preferenceForm.merchant_id"
              @input="$v.preferenceForm.merchant_id.$touch()"
            />
          </div>
        </div>

        <button
          class="rr-button rr-button--lg rr-button--primary-solid mt-4"
          data-test="save-button"
          :disabled="
            $v.preferenceForm.$invalid ||
              !preferenceForm.isDirty ||
              (merchantIdIsDirty && ($v.$invalid || !merchantVerified)) ||
              verifying
          "
          @click="savePreferences()"
        >
          Save
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { requiredIf, minLength } from "vuelidate/lib/validators";
import { validationMixin } from "vuelidate";

import Form from "@/classes/Form";
import { debounce } from "lodash";

export default {
  mixins: [validationMixin],

  computed: {
    ...mapGetters([
      "paymentPartner",
      "merchant_username",
      "merchant_password",
      "merchant_id",
      "hide_pos_sales",
      "subscription"
    ]),

    merchantIdIsDirty() {
      return this.preferenceForm.dirtyKeys.includes("merchant_id");
    }
  },

  watch: {
    "preferenceForm.merchant_username": function() {
      if (this.preferenceForm.merchant_id) {
        this.preferenceForm.merchant_id = null;
      }
    },
    "preferenceForm.merchant_password": function() {
      if (this.preferenceForm.merchant_id) {
        this.preferenceForm.merchant_id = null;
      }
    },
    "preferenceForm.merchant_id": function(val) {
      if (val && this.preferenceForm.isKeyDirty("merchant_id")) {
        if (this.merchantVerified) {
          this.merchantVerified = false;
        }

        this.preferenceForm.merchant_id = val.replace(/[^0-9]/g, "");
        this.verifyMerchant();
      }
    }
  },

  data() {
    return {
      preferenceForm: new Form({
        hide_pos_sales: false,
        using_merchant_partner: false,
        merchant_username: null,
        merchant_password: null,
        merchant_id: null
      }),
      merchantVerified: false,
      verifying: false,
      initialRequestSent: false
    };
  },

  mounted() {
    this.preferenceForm.update({
      hide_pos_sales: this.hide_pos_sales,
      using_merchant_partner: this.paymentPartner,
      merchant_username: this.merchant_username,
      merchant_password: this.merchant_password,
      merchant_id: this.merchant_id
    });

    setTimeout(() => {
      this.preferenceForm.update({
        merchant_id: this.merchant_id
      });
    }, 150);
  },

  methods: {
    savePreferences() {
      this.$store
        .dispatch("updatePreferences", this.preferenceForm.dirtyData)
        .then(preferences => {
          this.preferenceForm.update(preferences);
        });
    },

    requestApplication() {
      if (!this.subscription)
        return this.$toasted.show(
          "Merchant processing services are only available for subscribed users.",
          { type: "info" }
        );
      this.$intercom.showNewMessage(
        "I would like to request a merchant application."
      );
    },

    verifyMerchant: debounce(function() {
      if (this.preferenceForm.merchant_id.length >= 12 && !this.verifying) {
        this.initialRequestSent = true;
        this.verifying = true;
        this.$store
          .dispatch("verifyMerchant", this.preferenceForm.data)
          .then(() => {
            this.merchantVerified = true;
          })
          .catch(() => {
            this.merchantVerified = false;
          })
          .finally(() => {
            this.verifying = false;
          });
      }
    }, 500)
  },

  validations() {
    return {
      preferenceForm: {
        merchant_username: {
          required: requiredIf(function() {
            return this.preferenceForm.using_merchant_partner;
          })
        },
        merchant_password: {
          required: requiredIf(function() {
            return this.preferenceForm.using_merchant_partner;
          })
        },
        merchant_id: {
          required: requiredIf(function() {
            return this.preferenceForm.using_merchant_partner;
          }),
          minLength: minLength(12)
        }
      }
    };
  }
};
</script>
