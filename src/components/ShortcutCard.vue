<template>
  <div @click="redirectToLink(link)" class="shortcut-card">
    <h2>{{ title }}</h2>

    <ion-icon name="chevron-forward-outline" />
  </div>
</template>

<script>
import { IonIcon } from "@ionic/vue";
import { defineComponent } from "vue";

export default defineComponent({
  name: "ShortcutCard",
  components: {
    IonIcon,
  },
  props: {
    link: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
  },
  methods: {
    redirectToLink(link) {
      window.location.href = link;
    },
  },
});
</script>

<style scoped>
.shortcut-card {
  background: black;
  border-radius: 15px;
  display: flex;
  padding-inline: 1.5rem;
  margin-inline: 10px;
  justify-content: space-between;
  height: 100%;
}

.shortcut-card > ion-icon{
  height: 100%;
  width: 12%;
  color: red;/*gray*/
}

h2 {
  margin: 0;
  padding-bottom: 1.5rem;
  padding-top: 1.5rem;
}

.shortcut-card {
  transition: box-shadow 0.2s ease-in-out;
  /*box-shadow: 0 0 0 rgba(255, 0, 0, 0);/*rgba(255, 255, 255, 0)*/
}

.shortcut-card:hover {
  box-shadow: 0 0 20px rgba(255, 0, 0, 0.5); /*rgba(255, 255, 255, 0)*/
}

.shortcut-card:hover ion-card-title {
  color: #fff;
}
</style>
