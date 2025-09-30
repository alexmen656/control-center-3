<template>
  <div v-if="showTouchBar" class="touch-bar" :class="{ 'touch-bar-dark': isDarkMode }">
    <div class="touch-bar-content">
      <div class="touch-bar-item" @click="goHome">
        <ion-icon name="home-outline" :class="{ active: isActive('/') }"></ion-icon>
        <span class="touch-bar-label">Home</span>
      </div>
      <div class="touch-bar-item" @click="goToProjects">
        <ion-icon name="folder-outline" :class="{ active: isActive('/manage/projects') || isActive('/info/projects') || isActive('/new/project') }"></ion-icon>
        <span class="touch-bar-label">Projects</span>
      </div>
      <div class="touch-bar-item" @click="goToTools">
        <ion-icon name="construct-outline" :class="{ active: isActive('/info') && !isActive('/info/projects') }"></ion-icon>
        <span class="touch-bar-label">Tools</span>
      </div>
      <div class="touch-bar-item" @click="goToBookmarks">
        <ion-icon name="bookmark-outline" :class="{ active: isActive('/bookmarks') }"></ion-icon>
        <span class="touch-bar-label">Bookmarks</span>
      </div>
      <div class="touch-bar-item" @click="goToAccount">
        <ion-icon name="person-outline" :class="{ active: isActive('/my-account') }"></ion-icon>
        <span class="touch-bar-label">Account</span>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { isPlatform } from '@ionic/vue';

export default defineComponent({
  name: 'TouchBar',
  computed: {
    showTouchBar() {
      // Show on iOS and Android mobile devices
      return isPlatform('ios') || isPlatform('android') || 
             (isPlatform('mobile') && window.innerWidth <= 768);
    },
    isDarkMode() {
      return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
  },
  methods: {
    goHome() {
      this.$router.push('/');
    },
    goToProjects() {
      this.$router.push('/manage/projects');
    },
    goToTools() {
      this.$router.push('/info');
    },
    goToBookmarks() {
      this.$router.push('/bookmarks');
    },
    goToAccount() {
      this.$router.push('/my-account');
    },
    isActive(path) {
      if (path === '/') {
        return this.$route.path === '/';
      }
      return this.$route.path.startsWith(path);
    }
  }
});
</script>

<style scoped>
.touch-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  z-index: 1000;
  height: 84px;
  padding-bottom: env(safe-area-inset-bottom, 20px);
  box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1);
}

.touch-bar-dark {
  background: rgba(30, 30, 30, 0.95);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.3);
}

.touch-bar-content {
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 64px;
  padding: 0 16px;
  max-width: 500px;
  margin: 0 auto;
}

.touch-bar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 12px;
  transition: all 0.2s ease;
  cursor: pointer;
  min-width: 60px;
  -webkit-tap-highlight-color: transparent;
}

.touch-bar-item:active {
  transform: scale(0.95);
  background: rgba(var(--ion-color-primary-rgb), 0.1);
}

.touch-bar-item ion-icon {
  font-size: 24px;
  color: var(--ion-color-medium);
  transition: color 0.2s ease;
  margin-bottom: 2px;
}

.touch-bar-item ion-icon.active {
  color: var(--ion-color-primary);
}

.touch-bar-label {
  font-size: 10px;
  font-weight: 500;
  color: var(--ion-color-medium);
  transition: color 0.2s ease;
  margin-top: 2px;
  text-align: center;
  line-height: 1;
}

.touch-bar-item:hover .touch-bar-label,
.touch-bar-item ion-icon.active + .touch-bar-label {
  color: var(--ion-color-primary);
}

/* Add bottom padding to main content when touch bar is visible */
.has-touch-bar #main-content {
  padding-bottom: calc(84px + env(safe-area-inset-bottom, 20px)) !important;
}

/* iPhone X and newer home indicator accommodation */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  .touch-bar {
    padding-bottom: calc(env(safe-area-inset-bottom) + 20px);
    height: calc(84px + env(safe-area-inset-bottom));
  }
}

/* Very small screens adjustments */
@media only screen and (max-width: 360px) {
  .touch-bar-content {
    padding: 0 8px;
  }
  
  .touch-bar-item {
    min-width: 50px;
    padding: 6px 8px;
  }
  
  .touch-bar-item ion-icon {
    font-size: 22px;
  }
  
  .touch-bar-label {
    font-size: 9px;
  }
}

/* Haptic feedback style for iOS */
@media (hover: none) and (pointer: coarse) {
  .touch-bar-item:active {
    background: rgba(var(--ion-color-primary-rgb), 0.15);
  }
}
</style>