<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1">Online Site</h1>
    </div>

    <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
      <div>
        <div class="rr-field">
          <label class="rr-field__label">
            Site URL
            <span class="rr-field__label-required">Cannot be changed</span>
          </label>

          <div class="flex">
            <span class="rr-field__input-label border-r-0 rounded-l-md">
              shop.retailright.app/store/
            </span>
            <input
              class="rr-field__input rounded-l-none"
              type="text"
              data-test="slug-input"
              placeholder="example-site-url"
              v-model="slug"
            />
          </div>
        </div>

        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          data-test="submit-button"
          :disabled="!slug"
          @click.stop="saveSlug()"
          v-text="'Save'"
        />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      slug: null
    };
  },

  watch: {
    slug(slug) {
      if (slug.includes(" ")) {
        this.slug = slug.replace(" ", "-");
      }
    }
  },

  methods: {
    saveSlug() {
      this.$store.dispatch("saveSlug", this.slug).then(() => {
        this.$toasted.show("Successfully set site URL.", { type: "success" });
        this.$router.push({ name: "preferences.stores" });
      });
    }
  }
};
</script>
