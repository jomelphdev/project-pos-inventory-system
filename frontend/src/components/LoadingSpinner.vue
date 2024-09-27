<template>
  <svg
    class="animate-spin text-blue-600"
    :class="sizeClass"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
  >
    <circle
      class="opacity-50"
      cx="12"
      cy="12"
      r="10"
      stroke="currentColor"
      stroke-width="4"
    ></circle>
    <path
      fill="currentColor"
      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
    ></path>
  </svg>
</template>

<script>
export default {
  name: "LoadingSpinner",

  props: {
    size: String,
  },

  watch: {
    size: {
      immediate: true,
      handler() {
        switch (this.size) {
          case "sm":
            this.sizeClass = "h-4 w-4";
            break;
          case "lg":
            this.sizeClass = "h-8 w-8";
            break;
          case "xl":
            this.sizeClass = "h-12 w-12";
            break;
        }
      },
    },
  },

  data() {
    return {
      sizeClass: "h-6 w-6",
    };
  },
};
</script>

<style></style>
