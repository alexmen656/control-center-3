<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="storefront-outline" title="Module Store" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Module Store</h1>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="loadModules">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="cube-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ modules.length }}</h3>
              <p>Total Modules</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ addedCount }}</h3>
              <p>Added</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="download-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ availableCount }}</h3>
              <p>Available</p>
            </div>
          </div>
        </div>

        <!-- Modules Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Available Modules</h3>
              <span class="entry-count">{{ filteredModules.length }} module{{ filteredModules.length !== 1 ? 's' : ''
              }}</span>
            </div>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Search modules..." v-model="keyword" @input="handleSearch">
            </div>
          </div>

          <div class="card-content">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading modules...</p>
            </div>

            <div v-else-if="filteredModules.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="cube-outline" class="no-data-icon"></ion-icon>
                <h4>No Modules Found</h4>
                <p>{{ keyword ? 'No modules match your search.' : 'No modules available.' }}</p>
              </div>
            </div>

            <div v-else class="modules-grid">
              <div v-for="module in filteredModules" :key="module.ref" class="module-card"
                :class="{ 'is-added': module.status === 'added', 'is-adding': module.status === 'adding' }">
                <div class="module-header">
                  <div class="module-icon-wrapper">
                    <ion-icon v-if="module.icon" :name="module.icon" class="module-icon" alt="" />
                    <ion-icon v-else name="cube-outline" class="module-icon"></ion-icon>
                  </div>

                  <div class="module-info">
                    <h4 class="module-title">{{ module.display_name || module.name }}</h4>
                    <p class="module-desc">{{ module.description || 'No description available' }}</p>
                  </div>
                </div>
                <!-- Progress for adding -->
                <div v-if="module.status === 'adding'" class="progress-wrapper">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: module.progress + '%' }"></div>
                  </div>
                  <span class="progress-text">{{ module.progress }}%</span>
                </div>

                <div class="module-actions">
                  <button v-if="module.status === 'available'" class="action-btn primary" @click="addModule(module)">
                    <ion-icon name="add-outline"></ion-icon>
                    Add Module
                  </button>
                  <button v-else-if="module.status === 'added'" class="action-btn success"
                    @click="removeModule(module)">
                    <ion-icon name="checkmark-outline"></ion-icon>
                    Added
                  </button>
                  <button v-else-if="module.status === 'adding'" class="action-btn disabled" disabled>
                    <ion-icon name="sync-outline" class="spinning"></ion-icon>
                    Adding...
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import lunr from "lunr";
import SiteTitle from "@/components/SiteTitle.vue";

export default {
  name: "StoreOverview",
  components: {
    SiteTitle,
  },
  data() {
    return {
      modules: [],
      keyword: "",
      searchIndex: null,
      searchResults: [],
      loading: true,
      addedModuleNames: [],
    };
  },
  computed: {
    displayModules() {
      if (this.keyword && this.searchResults.length > 0) {
        return this.searchResults;
      } else if (this.keyword && this.searchResults.length === 0) {
        return [];
      }
      return this.modules;
    },
    filteredModules() {
      return this.displayModules;
    },
    addedCount() {
      return this.modules.filter(m => m.status === 'added').length;
    },
    availableCount() {
      return this.modules.filter(m => m.status === 'available').length;
    }
  },
  async created() {
    await this.loadModules();
  },
  methods: {
    async loadModules() {
      this.loading = true;

      try {
        // Load added modules
        const addedResponse = await this.$axios.post(
          "tools.php",
          this.$qs.stringify({
            getProjectTools: "getProjectTools",
            project: this.$route.params.project,
          })
        );

        if (addedResponse.data) {
          this.addedModuleNames = addedResponse.data.map(tool => tool.name);
        }

        // Load available modules
        const modulesResponse = await this.$axios.post(
          "install.php",
          this.$qs.stringify({
            getAvailableModules: "getAvailableModules"
          })
        );

        if (modulesResponse.data) {
          this.modules = modulesResponse.data.map(module => ({
            ...module,
            progress: 0,
            status: this.addedModuleNames.includes(module.display_name) ? 'added' : 'available'
          }));

          // Build search index
          this.searchIndex = lunr(function () {
            this.ref("ref");
            this.field("name");
            this.field("display_name");
            this.field("description");

            modulesResponse.data.forEach((module) => {
              this.add({
                ref: module.ref,
                name: module.name,
                display_name: module.display_name,
                description: module.description || ""
              });
            });
          });
        }
      } catch (error) {
        console.error('Error loading modules:', error);
        alert("Failed to load modules");
      } finally {
        this.loading = false;
      }
    },

    handleSearch() {
      this.searchResults = [];

      if (this.keyword && this.searchIndex) {
        try {
          const results = this.searchIndex.search(this.keyword);
          results.forEach((result) => {
            const module = this.modules.find(m => m.ref === result.ref);
            if (module) {
              this.searchResults.push(module);
            }
          });
        } catch (e) {
          // If search fails, show all modules
          this.searchResults = [];
        }
      }
    },

    addModule(module) {
      const moduleIndex = this.modules.findIndex(m => m.ref === module.ref);
      if (moduleIndex === -1) return;

      this.modules[moduleIndex].status = "adding";

      this.$axios.post(
        "install.php",
        this.$qs.stringify({
          install: "install",
          moduleID: module.id,
          project: this.$route.params.project,
        })
      ).then((response) => {
        if (response.data.includes("success")) {
          // Simulate progress
          const intervalId = setInterval(() => {
            if (this.modules[moduleIndex].progress < 100) {
              this.modules[moduleIndex].progress += 5;
            } else {
              this.modules[moduleIndex].status = "added";
              this.modules[moduleIndex].progress = 0;
              this.emitter.emit("updateSidebar");
              clearInterval(intervalId);
            }
          }, 50);
        } else {
          this.modules[moduleIndex].status = "available";
          this.modules[moduleIndex].progress = 0;
          alert("Failed to add module: " + response.data);
        }
      }).catch(() => {
        this.modules[moduleIndex].status = "available";
        this.modules[moduleIndex].progress = 0;
        alert("Failed to add module");
      });
    },

    removeModule(module) {
      const moduleIndex = this.modules.findIndex(m => m.ref === module.ref);
      if (moduleIndex === -1) return;

      if (!confirm(`Remove "${module.display_name}" from your project?`)) {
        return;
      }

      this.$axios
        .post(
          "install.php",
          this.$qs.stringify({
            deinstall: "deinstall",
            moduleID: module.id,
            project: this.$route.params.project,
          })
        )
        .then((response) => {
          if (response.data.includes("success")) {
            this.modules[moduleIndex].status = "available";
            this.modules[moduleIndex].progress = 0;
            this.emitter.emit("updateSidebar");
          } else {
            alert("Failed to remove module: " + response.data);
          }
        })
        .catch(() => {
          alert("Failed to remove module");
        });
    },
  },
};
</script>

<style scoped>
.modern-content {
  --background: #f5f7fa;
  --surface: #ffffff;
  --border: #e1e4e8;
  --text-primary: #24292f;
  --text-secondary: #57606a;
  --text-muted: #8c959f;
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --radius: 8px;
  --radius-lg: 12px;
  --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
}

.action-btn.primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
  /*box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);*/
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover {
  background: var(--background);
  color: var(--text-primary);
}

.action-btn.success {
  background: var(--success-color);
  color: white;
}

.action-btn.success:hover {
  background: #047857;
}

.action-btn.disabled {
  background: var(--warning-color);
  color: white;
  opacity: 0.8;
  cursor: not-allowed;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: rgba(37, 99, 235, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 24px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: var(--background);
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  padding: 4px 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

/* Search Box */
.search-box {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 280px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 18px;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Card Content */
.card-content {
  padding: 24px;
}

/* Loading & Empty States */
.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.4;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0;
  font-size: 14px;
}

/* Modules Grid */
.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.module-card {
  display: flex;
  flex-direction: column;
  padding: 20px;
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.3s ease;
}

.module-card:hover {
  /*border-color: var(--primary-color);*/
  box-shadow: var(--shadow-lg);
}

.module-card.is-added {
  border-color: var(--success-color);
  background: rgba(5, 150, 105, 0.05);
}

.module-card.is-adding {
  border-color: var(--warning-color);
  background: rgba(217, 119, 6, 0.05);
}

.module-icon-wrapper {
  width: 64px;
  height: 64px;
  margin-bottom: 16px;
  border-radius: var(--radius);
  background: var(--background);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: var(--shadow);
}

.module-icon {
  width: 40px;
  height: 40px;
  color: white;
  font-size: 40px;
  object-fit: contain;
}

.module-info {
  flex: 1;
  margin-bottom: 16px;
}

.module-title {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;
}

.module-desc {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Progress */
.progress-wrapper {
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: var(--border);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color) 0%, #8b5cf6 100%);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-color);
  min-width: 36px;
  text-align: right;
}

/* Module Actions */
.module-actions {
  display: flex;
  gap: 8px;
}

.module-actions .action-btn {
  width: 100%;
  justify-content: center;
}

/* Spinning Animation */
.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .module-card.is-added {
    background: rgba(5, 150, 105, 0.12);
  }

  .module-card.is-adding {
    background: rgba(217, 119, 6, 0.12);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: stretch;
  }

  .header-actions .action-btn {
    flex: 1;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    min-width: 100%;
  }

  .modules-grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .modules-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.module-header {
  display: flex;
}
</style>
