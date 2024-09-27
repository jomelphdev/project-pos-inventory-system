<template>
  <div>
    <div class="mb-6 text-xs leading-4 font-medium text-gray-500 uppercase">
      Account
    </div>
    <router-link :to="{ name: 'account.profile' }" class="block mb-4"
      >Profile</router-link
    >
    <router-link :to="{ name: 'account.password' }" class="block mb-4"
      >Password</router-link
    >
  </div>
</template>

<script>
export default {};
</script>
