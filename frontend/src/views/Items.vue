<template>
  <router-view :key="$route.fullPath" />
</template>

<script>
export default {
  name: "Items",

  data() {
    return {};
  },

  metaInfo: {
    title: "Items / "
  }
};
</script>
