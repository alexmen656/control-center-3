<template>
  <div class="spinner-overlay"><!--v-if="show" -->
    <ion-spinner name="crescent" color="danger" class="spinner"></ion-spinner>
  </div>
</template>

<script>
export default {
  name: "LoadingSpinner",
 /* data() {
    return {
        show: false
    };
  },
 mounted() {
  //  setTimeout(() => {
      this.show = true;
  //  }, 500);
  },*/
};
</script>

<style scoped>
.spinner-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.85);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.spinner {
  --color: #e53935;
  width: 64px;
  height: 64px;
}
</style>