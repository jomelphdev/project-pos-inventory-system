<template>
  <transition name="fade-in" appear>
    <div
      class="blank-state max-w-full mx-auto shadow-lg bg-white p-12 rounded-md"
    >
      <div class="blank-state__header mb-8" v-if="$slots.header">
        <h1 class="h1">
          <slot name="header" />
        </h1>
      </div>

      <div class="blank-state__body" v-if="$slots.body">
        <slot name="body" />
      </div>

      <div class="blank-state__footer mt-8" v-if="$slots.footer">
        <slot name="footer" />
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: "BlankState"
};
</script>
