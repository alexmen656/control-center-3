<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-left">
            <button class="back-btn" @click="$router.go(-1)">
              <ion-icon name="arrow-back-outline"></ion-icon>
            </button>
            <div>
              <h1>App Settings</h1>
              <p>Customize your app preferences and appearance</p>
            </div>
          </div>
        </div>

        <!-- Settings Sections -->
        <div class="settings-sections">
          <!-- Theme Settings -->
          <div class="settings-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="color-palette-outline"></ion-icon>
              </div>
              <div>
                <h3>App Theme</h3>
                <p>Choose your preferred color scheme</p>
              </div>
            </div>

            <div class="card-content">
              <div class="theme-grid">
                <div v-for="themeOption in themeOptions" :key="themeOption.value" class="theme-option"
                  :class="{ active: theme === themeOption.value }" @click="selectTheme(themeOption.value)">
                  <div class="theme-preview" :style="{ background: themeOption.color }">
                    <div class="theme-dot"></div>
                  </div>
                  <div class="theme-info">
                    <h4>{{ themeOption.name }}</h4>
                    <p>{{ themeOption.description }}</p>
                  </div>
                  <div class="theme-radio">
                    <ion-radio :value="themeOption.value" v-model="theme"></ion-radio>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Display Settings -->
          <div class="settings-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="phone-portrait-outline"></ion-icon>
              </div>
              <div>
                <h3>Display Settings</h3>
                <p>Configure display preferences</p>
              </div>
            </div>

            <div class="card-content">
              <div class="settings-list">
                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Dark Mode</h4>
                    <p>Use system default or override</p>
                  </div>
                  <div class="setting-control">
                    <ion-toggle color="primary"></ion-toggle>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Compact View</h4>
                    <p>Show more content in less space</p>
                  </div>
                  <div class="setting-control">
                    <ion-toggle color="primary"></ion-toggle>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Font Size</h4>
                    <p>Adjust text size for better readability</p>
                  </div>
                  <div class="setting-control">
                    <select class="modern-select">
                      <option value="small">Small</option>
                      <option value="medium" selected>Medium</option>
                      <option value="large">Large</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notifications -->
          <div class="settings-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="notifications-outline"></ion-icon>
              </div>
              <div>
                <h3>Notifications</h3>
                <p>Manage notification preferences</p>
              </div>
            </div>

            <div class="card-content">
              <div class="settings-list">
                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Push Notifications</h4>
                    <p>Receive notifications on your device</p>
                  </div>
                  <div class="setting-control">
                    <ion-toggle color="primary"></ion-toggle>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Email Notifications</h4>
                    <p>Get updates via email</p>
                  </div>
                  <div class="setting-control">
                    <ion-toggle color="primary"></ion-toggle>
                  </div>
                </div>

                <div class="setting-item">
                  <div class="setting-info">
                    <h4>Sound</h4>
                    <p>Play sound for notifications</p>
                  </div>
                  <div class="setting-control">
                    <ion-toggle color="primary"></ion-toggle>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Save Button -->
          <div class="save-section">
            <button class="action-btn primary save-btn" @click="setTheme">
              <ion-icon name="checkmark-outline"></ion-icon>
              <span>Save Settings</span>
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import { store } from "@/theme/theme";
import { closeSharp, saveSharp } from "ionicons/icons";

export default defineComponent({
  name: "SettingsModal",
  data() {
    return {
      theme: localStorage.getItem("themeSet") || "darkBlue",
      mode: "dark",
      themeOptions: [
        {
          name: "Red",
          value: "darkRed",
          color: "#d50000",
          description: "Bold and energetic"
        },
        {
          name: "Pink",
          value: "darkPink",
          color: "#c51162",
          description: "Playful and vibrant"
        },
        {
          name: "Purple",
          value: "darkPurple",
          color: "#aa00ff",
          description: "Creative and modern"
        },
        {
          name: "Deep Purple",
          value: "darkDarkPurple",
          color: "#6200ea",
          description: "Rich and elegant"
        },
        {
          name: "Indigo",
          value: "darkIndigo",
          color: "#304ffe",
          description: "Professional and calm"
        },
        {
          name: "Blue",
          value: "darkBlue",
          color: "#2962ff",
          description: "Classic and reliable"
        },
        {
          name: "Light Blue",
          value: "darkLightBlue",
          color: "#0091ea",
          description: "Fresh and modern"
        },
        {
          name: "Cyan",
          value: "darkCyan",
          color: "#00b8d4",
          description: "Cool and refreshing"
        },
        {
          name: "Teal",
          value: "darkTeal",
          color: "#00bfa5",
          description: "Balanced and natural"
        },
        {
          name: "Green",
          value: "darkGreen",
          color: "#00c853",
          description: "Growth and harmony"
        },
        {
          name: "Light Green",
          value: "darkLightGreen",
          color: "#64dd17",
          description: "Energetic and positive"
        },
        {
          name: "Yellow",
          value: "darkYellow",
          color: "#ffab00",
          description: "Bright and cheerful"
        },
        {
          name: "Orange",
          value: "darkOrange",
          color: "#ff6d00",
          description: "Warm and inviting"
        }
      ]
    };
  },
  methods: {
    selectTheme(themeValue: string) {
      this.theme = themeValue;
    },
    setTheme() {
      localStorage.setItem("themeSet", this.theme);
      store.setItem();
      // Optional: Show success message or navigate back
      this.$router.go(-1);
    },
  },
  setup() {
    return {
      closeSharp,
      saveSharp,
    };
  },
});
</script>

<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.back-btn:hover {
  background: var(--background);
  color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.back-btn ion-icon {
  font-size: 20px;
}

.header-left h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-left p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Settings Sections */
.settings-sections {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.header-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
}

.header-icon ion-icon {
  font-size: 24px;
}

.card-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.card-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.card-content {
  padding: 24px;
}

/* Theme Grid */
.theme-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

.theme-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.theme-option:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow);
}

.theme-option.active {
  border-color: var(--primary-color);
  background: #eff6ff;
  box-shadow: var(--shadow-md);
}

.theme-preview {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.theme-dot {
  width: 12px;
  height: 12px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 50%;
}

.theme-info {
  flex: 1;
}

.theme-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.theme-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.theme-radio {
  flex-shrink: 0;
}

/* Settings List */
.settings-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.setting-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.setting-item:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow);
}

.setting-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.setting-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.setting-control {
  flex-shrink: 0;
}

.modern-select {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Save Section */
.save-section {
  display: flex;
  justify-content: center;
  padding: 24px 0;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border: 1px solid var(--primary-color);
  box-shadow: var(--shadow);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.save-btn {
  padding: 16px 32px;
  font-size: 16px;
}

.action-btn ion-icon {
  font-size: 16px;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .theme-option.active {
    background: #1e293b;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .card-header,
  .card-content {
    padding: 20px;
  }

  .theme-grid {
    grid-template-columns: 1fr;
  }

  .setting-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .setting-control {
    align-self: stretch;
    display: flex;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .header-left h1 {
    font-size: 24px;
  }

  .card-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
  }

  .theme-option {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}
</style>
