<template>
  <div class="rr-field" v-if="show" :data-test="`${headerLabel}-custom-select`">
    <div class="rr-field__label">
      {{ headerLabel }}

      <span
        class="rr-field__label-required"
        :data-test="`${headerLabel}-custom-select-indicator`"
        v-if="required"
      >
        Required
      </span>
    </div>
    <div :data-test="`${headerLabel}-custom-select-options`">
      <div
        v-for="option in displayOptions"
        :key="option.id"
        class="rr-field__radio"
      >
        <input
          type="radio"
          v-model="selectedOptionIdLocal"
          :id="`${headerLabel}.${option.id}`"
          :value="option.id"
          :disabled="disabled || !!option.deleted_at"
          :data-test="`${headerLabel}-custom-select-option-${option.id}`"
          class="rr-field__radio-input"
          @click="selectOption(option)"
        />
        <label
          :for="`${headerLabel}.${option.id}`"
          class="rr-field__radio-label"
          :class="{ 'text-gray-500': disabled || !!option.deleted_at }"
          >{{ option.name }}</label
        >
      </div>
    </div>
    <div
      v-if="options.length > 3 && !allOptionsShowing"
      class="rr-field__radio"
    >
      <label
        class="rr-field__radio-more"
        :data-test="`${headerLabel}-custom-select-showAll`"
        @click="showAllOptions()"
      >
        Show All
      </label>
    </div>
    <div v-if="displayOptions.length > 3" class="rr-field__radio">
      <label
        class="rr-field__radio-label"
        :data-test="`${headerLabel}-custom-select-back`"
        @click="resetOptions()"
      >
        Back
      </label>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    headerLabel: String,
    options:
      Array[
        {
          id: Number,
          name: String
        }
      ],
    selectedOptionId: Number,
    required: {
      type: Boolean,
      default: false
    },
    show: {
      type: Boolean,
      default: true
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      displayOptions: [],
      selectedOptionIdLocal: null
    };
  },

  watch: {
    options: {
      handler() {
        this.resetOptions();
      },
      immediate: true
    },
    allOptionsShowing(bool) {
      return this.$emit("options-extended", bool);
    },
    selectedOptionId(id) {
      if (id != this.selectedOptionIdLocal) {
        this.selectedOptionIdLocal = id;
      }

      this.resetOptions();
    }
  },

  computed: {
    allOptionsShowing() {
      return (
        this.options.length > 3 &&
        this.options.length == this.displayOptions.length
      );
    }
  },

  methods: {
    showAllOptions() {
      this.displayOptions = [...this.options];

      if (this.selectedOptionId) {
        this.displayOptions.forEach((option, index) => {
          if (option.id == this.selectedOptionId) {
            this.displayOptions.splice(index, 1);
            this.displayOptions.unshift(option);
            return;
          }
        });
      }
    },

    resetOptions() {
      if (this.selectedOptionId) {
        const selectedOption = this.options.find(
          opt => opt.id == this.selectedOptionId
        );
        this.displayOptions = [selectedOption];
        this.selectedOptionIdLocal = this.selectedOptionId;
        return;
      }

      this.displayOptions = this.options.slice(0, 3);
    },

    selectOption(option) {
      const optionId = option.id == this.selectedOptionId ? null : option.id;

      this.$emit("update:selectedOptionId", optionId);
      this.$emit("option-selected", optionId);
    }
  }
};
</script>
