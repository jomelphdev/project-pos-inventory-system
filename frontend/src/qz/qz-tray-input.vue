<template>
  <div class="qz-tray--container qz-tray--input" v-if="hasParent && !hide">
    <div class="qz-tray--input--header--wrapper" v-if="!hideHeader">
      <div class="qz-tray--input--label--wrapper">
        <slot name="label">
          <h4 class="qz-tray--input--label">Input</h4>
        </slot>
      </div>

      <div class="qz-tray--input--type--wrapper">
        <slot name="type" :type="type">
          <h3 class="qz-tray--input--type">{{ type }}</h3>
        </slot>
      </div>
    </div>

    <slot></slot>

    <textarea name="" id="" cols="30" rows="10" v-model="pages">

    </textarea>

    <input type="text" v-model="pages" />
  </div>
</template>

<script>
import QzMixin from "@/qz/qz-mixin";
// import QzTrayInputIframe from "@/qz/qz-tray-input-iframe.vue";

export default {
  name: "QzTrayInput",

  label: "input",

  mixins: [QzMixin],

  components: {
    // QzTrayInputIframe
  },

  data() {
    return {
      pages: ''
    }
  },

  computed: {
    type: function() {
      return this.qzOptions.type && ["html"].indexOf(this.qzOptions.type) < 0
        ? "html"
        : this.qzOptions.type;
    }
  },

  watch: {
    pages: {
      immediate: true,
      handler: function(newPages) {
        this.$emitLocalAndRoot("pages-changed", newPages);
      }
    }
  }
};
</script>
