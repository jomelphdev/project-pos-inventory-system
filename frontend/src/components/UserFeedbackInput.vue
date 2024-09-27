<template>
  <div>
    <label class="rr-field__label" v-text="question.prompt" />

    <div v-if="!isSelect">
      <div
        v-for="(choice, key) in question.choices"
        :key="key"
        class="flex items-baseline"
      >
        <input
          type="checkbox"
          :value="choice"
          :id="`feature-${key}`"
          v-model="feedback"
          class="mr-2"
        />
        <label :for="`feature-${key}`" v-text="choice" class="text-sm" />
      </div>
      <div v-if="!question.choices">
        <input class="rr-field__input" type="text" v-model="feedback" />
      </div>
    </div>

    <div v-else>
      <select class="rr-field__input" v-model="feedback">
        <option
          v-for="(choice, index) in question.choices"
          :key="index"
          :value="choice"
          >{{ choice }}</option
        >
      </select>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    question: {
      type: Object,
      default: () => {
        return {
          prompt: "Feedback Prompt",
          feedback: ""
        };
      }
    },
    isSelect: {
      type: Boolean,
      default: false
    }
  },

  computed: {
    feedback: {
      get() {
        return this.question.feedback;
      },
      set(value) {
        this.$emit("update:feedback", value);
      }
    }
  }
};
</script>
