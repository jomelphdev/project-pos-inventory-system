<template>
  <transition name="fade-in" appear>
    <div class="modal-background" @click.stop="closeModal" v-if="show">
      <div class="modal mx-auto" :class="sizeClass" @click.stop="">
        <a
          href="#"
          class="modal__close"
          data-test="modalClose-button"
          @click.prevent.stop="closeModal"
        >
          Close
        </a>

        <div class="modal__header" v-if="$slots.header">
          <slot name="header" />
        </div>

        <div class="modal__body" v-if="$slots.body">
          <slot name="body" />
        </div>

        <div class="modal__footer" v-if="$slots.footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: "Modal",

  data() {
    return {
      show: false
    };
  },

  props: {
    size: {
      type: String,
      default: "3xl"
    }
  },

  computed: {
    sizeClass() {
      return `max-w-${this.size}`;
    }
  },

  methods: {
    closeModal() {
      this.show = false;
      this.$emit("closed");
    },

    openModal() {
      this.show = true;
    }
  }
};
</script>
