<template>
  <ion-header>
    <ion-toolbar class="header">
      <ion-buttons slot="start">
        <ion-menu-button></ion-menu-button>
      </ion-buttons>
      <ion-title @click="goToStart()" @dblclick="toggleSidebar()" class="logo-title">
        <div class="logo-container">
          <img class="logo-wordmark" src="/assets/brand/fringelo-wordmark.svg" alt="fringelo" />
        </div>
      </ion-title>
      <div slot="end" class="header-end">
        <GlobalSearch />
        <router-link style="height: 36px; margin-right: 5px" to="/my-account/">
          <Avatar :profileImg="user.profileImg" :firstName="user.firstName" :lastName="user.lastName"
            avatarColor="green" />
        </router-link>
      </div>
    </ion-toolbar>
  </ion-header>
</template>

<script>
import Avatar from "@/components/AvatarComponent.vue";
import GlobalSearch from "@/components/GlobalSearch.vue";
import { defineComponent } from "vue";
import { loadUserData, getUserData } from "@/userData";

export default defineComponent({
  name: "SiteHeader",
  emits: ['toggleSidebar'],
  components: {
    Avatar,
    GlobalSearch,
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
    const title = width > 380 ? "Fringelo" : "CCenter";

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
.header-end {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-title {
  font-size: 20px;
  cursor: pointer !important;
  padding: 0 !important;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 14px;
  height: 100%;
  justify-content: flex-start;
  padding: 4px 0;
}

.logo-wordmark {
  height: 34px;
  width: auto;
  display: block;
  transition: transform 0.2s ease;
}

.logo-title:active .logo-wordmark {
  transform: scale(0.98);
}

@media only screen and (max-width: 600px) {
  .logo-title {
    text-align: center;
  }

  .logo-container {
    justify-content: center;
  }

  .logo-wordmark {
    height: 24px;
  }
}

@media only screen and (max-width: 480px) {
  .logo-wordmark {
    height: 22px;
  }

  .logo-container {
    gap: 10px;
  }
}

ion-footer ion-toolbar {
  color: #000;
}

ion-header,
ion-toolbar,
.header {
  --background: #eff3f6;
  box-shadow: none;
}

@media (prefers-color-scheme: dark) {

  ion-header,
  ion-toolbar,
  .header {
    --background: #1e1e1e;
  }
}

ion-toolbar {
  height: 48px;
  display: flex;
  align-items: center;
  --min-height: 48px;
  --padding-start: 8px;
  --padding-end: 8px;
}

@media only screen and (max-width: 600px) {
  ion-header {
    position: relative !important;
  }

  ion-toolbar {
    height: 56px !important;
  }

  ion-toolbar,
  .header {
    height: 56px !important;
    --min-height: 56px !important;
    --background: #eff3f6 !important;
    background: #eff3f6 !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
    box-shadow: 0;
  }

  @media (prefers-color-scheme: dark) {

    ion-toolbar,
    .header {
      --background: #1e1e1e !important;
      background: #1e1e1e !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
  }
}
</style>
