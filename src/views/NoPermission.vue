<template>
  <div class="no-permission-container">
    <ion-icon name="lock-closed-outline" class="no-permission-icon"></ion-icon>
    <h1>Kein Zugriff</h1>
    <p>Du hast keine Berechtigung, diese Seite zu sehen.</p>
    <ion-button color="danger" @click="goHome">Zurück zur Startseite</ion-button>
  </div>
</template>

<script>
export default {
  name: "NoPermission",
  methods: {
    goHome() {
      this.$router.push("/");
    },
  },
};
</script>

<style scoped>
.no-permission-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: #fff5f5;
  text-align: center;
}
.no-permission-icon {
  font-size: 5rem;
  color: #e53935;
  margin-bottom: 1rem;
}
h1 {
  color: #e53935;
  margin-bottom: 0.5rem;
}
p {
  color: #333;
  margin-bottom: 2rem;
}
</style>
