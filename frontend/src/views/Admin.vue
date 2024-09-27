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
import Navigation from "./admin/Navigation";

export default {
  components: { Navigation }
};
</script>

<style></style>
