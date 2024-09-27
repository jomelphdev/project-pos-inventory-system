<template>
  <div class="container">
    <div class="flex flex-col md:flex-row justify-center">
      <div
        class="rr-card rr-card--shadow p-8 md:w-2/6 bg-transparent shadow-none hover:shadow-none"
        v-if="!createdUser"
      >
        <h1 class="h1">Create an Account</h1>
        <p class="max-w-md">
          Starting a 14-day trial is free and easy. <br />
          You’re just a few clicks away from...
        </p>
        <ul class="mt-4 list-disc text-sm mx-4 space-y-2">
          <li>
            Instant item lookup, price suggestions, and discount calculations.
          </li>

          <li>Multi-store/multi-state capabilities.</li>
          <li>Industry standard label printing &amp; barcode scanning.</li>
          <li>Intelligent inventory management &amp; history logs.</li>
          <li>
            Modern POS system with cash-drawer, scanner, and reciept support.
          </li>
          <li>Real time sales reporting &amp; order history.</li>
        </ul>
      </div>
      <div
        class="rr-card rr-card--shadow p-8 md:w-4/6"
        data-test="user-create-form"
        v-if="!createdUser"
      >
        <h1 class="h1">
          Step 1
          <span class="font-light">of 2</span>
        </h1>
        <form @submit.prevent="createUser">
          <h2 class="text-lg font-bold my-4">Account Info</h2>
          <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
            <div class="rr-field">
              <label class="rr-field__label">
                First Name

                <span
                  class="rr-field__label-required"
                  data-test="user-create-firstName-indicator"
                  v-if="!$v.userForm.first_name.required"
                >
                  Required
                </span>
              </label>
              <input
                class="rr-field__input"
                type="text"
                data-test="user-create-firstName-input"
                v-model="userForm.first_name"
                @input="suggestUsername()"
              />
            </div>
            <div class="rr-field">
              <label class="rr-field__label">
                Last Name

                <span
                  class="rr-field__label-required"
                  data-test="user-create-lastName-indicator"
                  v-if="!$v.userForm.last_name.required"
                >
                  Required
                </span>
              </label>
              <input
                class="rr-field__input"
                type="text"
                data-test="user-create-lastName-input"
                v-model="userForm.last_name"
                @input="suggestUsername()"
              />
            </div>
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Email

              <span
                class="rr-field__label-required"
                data-test="user-create-email-indicator"
                v-if="!$v.userForm.email.required"
              >
                Required
              </span>

              <span
                class="rr-field__label-required"
                v-if="!$v.userForm.email.email"
              >
                Invalid email format
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="user-create-email-input"
              v-model="userForm.email"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Username

              <span
                class="rr-field__label-required"
                data-test="user-create-username-indicator"
                v-if="!$v.userForm.username.required"
              >
                Required
              </span>

              <span
                class="rr-field__label-required"
                v-if="!$v.userForm.username.alphaNum"
              >
                No spaces A-Z 0-9 characters only
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="user-create-username-input"
              v-model="userForm.username"
              @input="usernameToLower()"
            />
          </div>
          <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
            <div class="rr-field">
              <label class="rr-field__label">
                Password

                <span
                  class="rr-field__label-required"
                  data-test="user-create-password-indicator"
                  v-if="!$v.userForm.password.required"
                >
                  Required
                </span>

                <span
                  class="rr-field__label-required"
                  data-test="user-create-minEight-indicator"
                  v-if="!$v.userForm.password.minLength"
                >
                  Minimum 8 characters
                </span>
              </label>
              <input
                class="rr-field__input"
                type="password"
                data-test="user-create-password-input"
                v-model="userForm.password"
              />
            </div>
            <div class="rr-field">
              <label class="rr-field__label">
                Confirm Password

                <span
                  class="rr-field__label-required"
                  data-test="user-create-passwordDoesntMatch-indicator"
                  v-if="!$v.userForm.passwordConfirm.sameAsPassword"
                >
                  Doesn't match
                </span>
              </label>
              <input
                class="rr-field__input"
                type="password"
                data-test="user-create-passwordConfirm-input"
                v-model="userForm.passwordConfirm"
              />
            </div>
          </div>
          <h2 class="text-lg font-bold my-4">Help us Improve</h2>
          <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
            <div class="rr-field">
              <div class="space-y-2">
                <UserFeedbackInput
                  :question="questions[0]"
                  :feedback.sync="questions[0].feedback"
                  data-test="user-feedback-0"
                />
              </div>
            </div>

            <div class="rr-field">
              <UserFeedbackInput
                :question="questions[1]"
                :feedback.sync="questions[1].feedback"
                data-test="user-feedback-1"
              />
              <UserFeedbackInput
                :question="questions[2]"
                :feedback.sync="questions[2].feedback"
                data-test="user-feedback-2"
              />
              <UserFeedbackInput
                :question="questions[3]"
                :feedback.sync="questions[3].feedback"
                :isSelect="true"
                data-test="user-feedback-3"
              />
            </div>
          </div>
          <div class="mt-4">
            <button
              class="rr-button rr-button--primary w-full rr-button--lg justify-center"
              data-test="user-create-button"
              :disabled="$v.userForm.$invalid"
              @click.prevent="createUser"
            >
              Create Account
            </button>
          </div>
        </form>
      </div>

      <div
        class="rr-card rr-card--shadow p-8 md:w-2/6 bg-transparent shadow-none hover:shadow-none"
        data-test="user-create-plans"
        v-if="createdUser"
      >
        <h1 class="h1">Choose your Plan</h1>
        <p class="max-w-md">
          Kick the tires for 14 days or select a plan to ensure your access is
          uninterrupted.
        </p>
      </div>
      <div v-if="createdUser" class="rr-card rr-card--shadow p-8 md:w-4/6">
        <h1 class="h1">Step 2 <span class="font-light">of 2</span></h1>
        <div class="mt-6">
          <Plan @free-trial-selected="freeTrialSelected" />
        </div>

        <UpdatePaymentMethodModal :ref="'paymentMethodModal'" />
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { validationMixin } from "vuelidate";
import {
  required,
  email,
  sameAs,
  alphaNum,
  minLength
} from "vuelidate/lib/validators";
import { generateUsername } from "@/helpers";
import { debounce } from "lodash";

import UpdatePaymentMethodModal from "@/views/billing/UpdatePaymentMethodModal";
import UserFeedbackInput from "@/components/UserFeedbackInput.vue";
import Plan from "@/views/billing/Plan";

import Form from "@/classes/Form";

export default {
  mixins: [validationMixin],

  components: {
    UpdatePaymentMethodModal,
    Plan,
    UserFeedbackInput
  },

  data() {
    return {
      userForm: new Form({
        id: null,
        first_name: "",
        last_name: "",
        username: "",
        password: "",
        passwordConfirm: "",
        email: "",
        subscription: null,
        feedback: []
      }),
      questions: [
        {
          prompt: "What features do you plan to use?",
          feedback: [],
          choices: [
            "Inventory management",
            "Label printing",
            "Manifest import & lookup",
            "POS",
            "EBT payment",
            "Employee access",
            "Online store",
            "Reporting"
          ]
        },
        {
          prompt: "How did you hear about RetailRight?",
          feedback: ""
        },
        {
          prompt: "What software/services does your business use?",
          feedback: ""
        },
        {
          prompt: "Estimated Annual  Revenue?",
          feedback: "",
          choices: [
            "",
            "0-100K",
            "100K-250K",
            "250K-1M",
            "1M-5M",
            "5M-10M",
            "10M+"
          ]
        }
      ],
      createdUser: null,
      billing_option: "",
      user: null
    };
  },

  computed: {
    ...mapGetters(["subscription", "currentUser", "token"])
  },

  watch: {
    billing_option(val) {
      if (val != "") {
        this.userForm.subscription = val;
        this.configureStripe();
      }
    },

    subscription() {
      this.$toasted.show(
        `Congratulations! Your account has been created and you still have 14 day trial period before your first charge.`,
        { type: "success" }
      );

      this.$router.push({ name: "login" });
    },

    questions: {
      handler() {
        this.userForm.feedback = this.questions
          .filter(question => {
            const feedback = question.feedback;

            if (Array.isArray(feedback) && feedback.length == 0) {
              return false;
            }

            return !!feedback;
          })
          .map(question => {
            return {
              prompt: question.prompt,
              feedback: question.feedback,
              origin: "sign-up"
            };
          });
      },
      deep: true
    }
  },

  mounted() {
    if (this.currentUser) {
      this.$router.push({ name: "scan" });
    }

    if (!this.currentUser && this.token) {
      this.createdUser = true;
    }

    if (window.Cypress) {
      window.UserCreate = {
        user: this.userForm
      };
    }
  },

  methods: {
    createUser() {
      this.$store
        .dispatch("createUser", {
          user: this.userForm,
          adminUser: true
        })
        .then(user => {
          this.createdUser = user;
        });
    },

    freeTrialSelected() {
      this.$toasted.show(
        "Congratulations! Your account has been created and your 14 day free trial has started.",
        { type: "success" }
      );
      this.$router.push({ name: "login" });

      setTimeout(() => {
        this.$store.dispatch("logout");
      }, 2500);
    },

    usernameToLower() {
      this.userForm.username = this.userForm.username.toLowerCase();
    },

    suggestUsername: debounce(function() {
      this.userForm.username = generateUsername(
        this.userForm.first_name,
        this.userForm.last_name
      );
    }, 250)
  },

  validations() {
    return {
      userForm: {
        first_name: { required },
        last_name: { required },
        username: { required, alphaNum },
        password: { required, minLength: minLength(8) },
        passwordConfirm: { sameAsPassword: sameAs("password") },
        email: { required, email }
      }
    };
  }
};
</script>
