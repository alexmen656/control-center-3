<template>
  <ion-header>
    <ion-toolbar class="header">
      <ion-buttons slot="start">
        <ion-menu-button></ion-menu-button>
      </ion-buttons>
      <ion-title @click="goToStart()" @dblclick="toggleSidebar()" class="logo-title">
        <div class="logo-container">
          <img class="logo-image" src="/assets/logo.png" alt="Logo"/>
          <span class="logo-text">Control Center</span>
        </div>
      </ion-title><!--{{ title }}-->
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
/* Logo Title Styling */
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

.logo-image {
  height: 32px;
  width: auto;
  transition: transform 0.2s ease;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.logo-text {
  font-weight: 700;
  font-size: 22px;
  color: var(--ion-color-primary);
  letter-spacing: -0.8px;
  line-height: 1;
  transition: color 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Hover effects */
/*.logo-title:hover .logo-image {
  transform: scale(1.05);
}

.logo-title:hover .logo-text {
  color: var(--ion-color-primary-shade);
}*/

.logo-title:active .logo-image {
  transform: scale(0.98);
}

/* Responsive adjustments */
@media only screen and (max-width: 600px) {
  .logo-title {
    text-align: center;
  }
  
  .logo-container {
    justify-content: center;
  }
  
  .logo-text {
    font-size: 18px;
  }
  
  .logo-image {
    height: 28px;
  }
}

@media only screen and (max-width: 480px) {
  .logo-text {
    font-size: 16px;
    letter-spacing: -0.3px;
  }
  
  .logo-image {
    height: 32px;
  }
  
  .logo-container {
    gap: 10px;
  }
}

/* Header styling */
ion-footer ion-toolbar {
  color: #000;
}

ion-header,
ion-toolbar,
.header {
  --background: #eff3f6;
  box-shadow: none;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

@media (prefers-color-scheme: dark) {
  ion-header,
  ion-toolbar,
  .header {
    --background: #1e1e1e;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

/* Mobile header improvements */
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
