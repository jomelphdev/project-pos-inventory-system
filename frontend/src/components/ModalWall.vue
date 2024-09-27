<template>
  <transition name="fade-in" appear>
    <div class="modal-background" v-if="show">
      <div class="modal max-w-3xl mx-auto" @click.stop="">
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
 * This modal has no click to close functionality.
 * It should only be used if the user is given a way to close the modal.
 */
export default {
  name: "ModalWall",

  data() {
    return {
      show: false,
    };
  },

  methods: {
    closeModal() {
      this.show = false;
    },

    openModal() {
      this.show = true;
    },
  },
};
</script>
