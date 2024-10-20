<template>
    <ion-spinner v-if="show" name="crescent"></ion-spinner>
</template>

<script>
export default {
  name: "LoadingSpinner",
  data() {
    return {
        show: false
    };
  },
 mounted() {
    setTimeout(() => {
      this.show = true;
    }, 500);
  },
};
</script>

<style scoped>
.spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}
</style>