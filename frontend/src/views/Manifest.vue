<template>
  <router-view :key="$route.fullPath" />
</template>

<script>
export default {
  name: "Manifest",

  data() {
    return {};
  }
};
</script>
