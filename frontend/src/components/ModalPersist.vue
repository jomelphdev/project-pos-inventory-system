<template>
  <transition name="fade-in" appear>
    <div class="modal-background" @click.stop="closeModal" v-show="show">
      <div class="modal mx-auto" :class="`max-w-${maxWidth}`" v-on:click.stop>
        <a
          href="#"
          class="modal__close"
          @click.prevent.stop="closeModal"
          data-test="closeModal-button"
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
/**
 * Only major difference between Modal and ModalPersist, is that
 * ModalPersist uses v-show so the component isn't destroyed when hidden.
 */
export default {
  name: "ModalPersist",

  data() {
    return {
      show: false
    };
  },

  props: {
    maxWidth: {
      type: String,
      default: "3xl"
    }
  },

  methods: {
    closeModal() {
      this.show = false;
      this.$store.dispatch("updateQzPanel", false);
    },

    openModal() {
      this.show = true;
    }
  }
};
</script>
