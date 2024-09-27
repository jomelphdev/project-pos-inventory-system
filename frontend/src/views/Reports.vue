<template>
  <div class="container">
    <div class="preferences-ui">
      <div class="preferences-ui__aside">
        <Navigation />
      </div>
      <div class="preferences-ui__main">
        <transition name="fade-in" mode="out-in" appear>
          <router-view :key="$route.fullPath" />
        </transition>
      </div>
    </div>
  </div>
</template>

<script>
import Navigation from "@/views/reports/Navigation";

export default {
  name: "Reports",

  components: {
    Navigation
  },

  metaInfo: {
    title: "Reports / "
  }
};
</script>

<style></style>
