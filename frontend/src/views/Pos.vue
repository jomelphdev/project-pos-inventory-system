<template>
  <router-view :key="componentKey" />
</template>

<script>
export default {
  name: "Pos",

  data() {
    return {
      componentKey: Date.now()
    };
  },

  mounted() {
    this.$root.$on("reload-pos", () => {
      if (Object.keys(this.$route.query).length != 0) {
        this.$router.push(this.$route.path);
      }

      this.$route.params.order = null;
      // this.componentKey = Date.now();
    });
  },

  metaInfo: {
    title: "POS / "
  }
};
</script>

<style lang="scss">
@import "@/assets/scss/components/pos-ui.scss";
</style>
