<template>
  <span class="text-sm ml-2">
    <a :href="link" class="flex items-center" target="_blank">
      <QuestionMarkCircleIcon size="16" />
      <span style="margin-left:1px;margin-bottom:1px;">
        {{ label }}
      </span>
    </a>
  </span>
</template>

<script>
import { QuestionMarkCircleIcon } from "@vue-hero-icons/outline";

export default {
  components: {
    QuestionMarkCircleIcon
  },
  props: {
    label: {
      type: String,
      default: "Help"
    },
    link: {
      type: String,
      default: ""
    }
  }
};
</script>
