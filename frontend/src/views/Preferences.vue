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
import Navigation from "@/views/preferences/Navigation";

export default {
  name: "Preferences",

  components: {
    Navigation
  },

  mounted() {
    if (!this.$route.query.store_id) {
      this.$store.dispatch("setPreferenceStore", null);
    }

    this.$store.dispatch("getPreferences", {
      append: "employees_with_permissions",
      with: "organization"
    });
  },

  data() {
    return {
      employees: this.$store.getters.employees
    };
  },

  metaInfo: {
    title: "Settings / "
  }
};
</script>
