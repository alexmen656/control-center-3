<template>
  <ion-header :translucent="true">
    <ion-toolbar class="header">
      <ion-buttons slot="start">
        <ion-menu-button></ion-menu-button>
      </ion-buttons>
      <ion-title @click="goToStart()" @dblclick="toggleSidebar()">{{ title }}</ion-title>
      <router-link
        style="height: 36px; margin-right: 5px"
        slot="end"
        to="/my-account/"
        ><Avatar
          :profileImg="user.profileImg"
          :firstName="user.firstName"
          :lastName="user.lastName"
          avatarColor="green"
        />
      </router-link>
    </ion-toolbar>
  </ion-header>
</template>

<script>
import Avatar from "@/components/AvatarComponent.vue";
import { defineComponent } from "vue";
import { loadUserData, getUserData } from "@/userData";

export default defineComponent({
  name: "SiteHeader",
  emits: ['toggleSidebar'],
  components: {
    Avatar,
  },
  data() {
    return {
      user: {},
    };
  },
  async mounted() {
    await loadUserData();
    this.user = await getUserData();
  },
  setup() {
    const width = document.body.clientWidth;
    const title = width > 380 ? "Control Center" : "CCenter";

    return {
      title: title,
    };
  },
  methods: {
    goToStart() {
     this.$router.push("/");
    },
    toggleSidebar() {
      this.$emit('toggleSidebar');
    },
  },
});
</script>

<style scoped>
ion-title {
  font-family: Chalkduster;
  font-size: 28px;
  color: var(--ion-color-primary);/*#ff0000*/
  text-align: left;
  cursor: pointer !important;
}

@media only screen and (max-width: 600px) {
  ion-title {
    text-align: center;
  }
}

ion-footer ion-toolbar {
  color: #000;
}

ion-title {
/*  color: red;*/
}

ion-header,
ion-toolbar,
.header {
  --background: #eff3f6;
  box-shadow: none;
}

ion-toolbar {
height: 48px;
display: flex;
align-items: center;
}
</style>
