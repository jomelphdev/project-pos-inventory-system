<template>
  <div>
    <div
      class="mb-6 text-xs leading-4 font-medium text-gray-500 uppercase"
      data-test="nav-title"
    >
      Admin Panel
    </div>

    <span>
      <router-link
        :to="{ name: 'admin.announcements' }"
        class="block mb-4"
        data-test="stores-nav"
        >Announcements</router-link
      >
    </span>
  </div>
</template>

<script>
export default {};
</script>

<style></style>
