<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="storefront-outline" title="Module Store"/>

      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Module Store</h1>
            <p>Discover and install modules for your project</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshModules">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="cube-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ totalModules }}</h3>
              <p>Available Modules</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ installedCount }}</h3>
              <p>Installed</p>
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
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="sync-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ installingCount }}</h3>
              <p>Installing</p>
            </div>
          </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-container">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input 
              type="text" 
              placeholder="Search modules..." 
              @input="handleInput($event)"
              v-model="keyword"
            >
          </div>
          <div class="filter-buttons">
            <button 
              class="filter-btn" 
              :class="{ active: selectedFilter === 'all' }" 
              @click="setFilter('all')"
            >
              All
            </button>
            <button 
              class="filter-btn" 
              :class="{ active: selectedFilter === 'installed' }" 
              @click="setFilter('installed')"
            >
              Installed
            </button>
            <button 
              class="filter-btn" 
              :class="{ active: selectedFilter === 'available' }" 
              @click="setFilter('available')"
            >
              Available
            </button>
          </div>
        </div>

        <!-- Modules Grid -->
        <div class="modules-container">
          <div v-if="loading" class="loading-state">
            <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
            <p>Loading modules...</p>
          </div>

          <div v-else-if="displayModules.length === 0" class="no-data-state">
            <ion-icon name="cube-outline" class="no-data-icon"></ion-icon>
            <h3>No Modules Found</h3>
            <p>{{ keyword ? 'No modules match your search criteria.' : 'No modules are available.' }}</p>
          </div>

          <div v-else class="modules-grid">
            <div 
              v-for="(module, index) in displayModules" 
              :key="module.ref || index"
              class="module-card"
              :class="getModuleCardClass(module)"
            >
              <div class="module-header">
                <div class="module-icon">
                  <ion-icon :name="module.icon || 'cube-outline'"></ion-icon>
                </div>
                <div class="module-info">
                  <h3 class="module-name">{{ module.display_name || module.name }}</h3>
                  <p class="module-description">{{ module.description || 'No description available' }}</p>
                </div>
                <div class="module-status">
                  <span 
                    class="status-badge"
                    :class="getStatusClass(module.status)"
                  >
                    <ion-icon :name="getStatusIcon(module.status)"></ion-icon>
                    {{ getStatusText(module.status) }}
                  </span>
                </div>
              </div>

              <!-- Progress Bar for Installing -->
              <div v-if="module.status === 'installing'" class="progress-container">
                <div class="progress-bar">
                  <div 
                    class="progress-fill" 
                    :style="{ width: module.progress + '%' }"
                  ></div>
                </div>
                <span class="progress-text">{{ module.progress }}%</span>
              </div>

              <!-- Module Actions -->
              <div class="module-actions">
                <button 
                  v-if="module.status === 'not_installed'" 
                  class="action-btn primary"
                  @click="installModule(module)"
                >
                  <ion-icon name="download-outline"></ion-icon>
                  Install
                </button>
                <button 
                  v-else-if="module.status === 'installed'" 
                  class="action-btn danger"
                  @click="uninstallModule(module)"
                >
                  <ion-icon name="trash-outline"></ion-icon>
                  Uninstall
                </button>
                <button 
                  v-else-if="module.status === 'installing'" 
                  class="action-btn secondary"
                  disabled
                >
                  <ion-icon name="sync-outline" class="spinning"></ion-icon>
                  Installing...
                </button>
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
  components: {
    SiteTitle,
  },
  data() {
    return {
      modules: [],
      keyword: "",
      searchIndex: [],
      results: [],
      loading: true,
      selectedFilter: 'all',
      installedModules: [],
    };
  },
  computed: {
    displayModules() {
      let filtered = this.results.length > 0 ? this.results : this.modules;
      
      // Apply status filter
      if (this.selectedFilter === 'installed') {
        filtered = filtered.filter(module => module.status === 'installed');
      } else if (this.selectedFilter === 'available') {
        filtered = filtered.filter(module => module.status === 'not_installed');
      }
      
      return filtered;
    },
    totalModules() {
      return this.modules.length;
    },
    installedCount() {
      return this.modules.filter(module => module.status === 'installed').length;
    },
    availableCount() {
      return this.modules.filter(module => module.status === 'not_installed').length;
    },
    installingCount() {
      return this.modules.filter(module => module.status === 'installing').length;
    }
  },
  mounted() {
    this.emitter.on("install", (data) => {
      this.installModule(data.moduleID, data.index);
    });

    this.emitter.on("deinstall", (data) => {
      this.uninstallModule(data.moduleID, data.index);
    });

    this.$watch(
      () => this.keyword,
      () => {
        this.results = [];

        if (this.keyword != "") {
          this.searchIndex.search(this.keyword).forEach((result) => {
            this.results.push(
              this.modules.find(({ ref }) => ref === result.ref)
            );
          });
        }
      }
    );
  },
  async created() {
    await this.loadModules();
  },
  methods: {
    async loadModules() {
      this.loading = true;
      
      try {
        // Load installed modules first
        const installedResponse = await this.$axios.post(
          "modules.php",
          this.$qs.stringify({ project: this.$route.params.project })
        );
        
        if (installedResponse.data) {
          installedResponse.data.forEach((module) => {
            this.installedModules.push(module.name);
          });
        }

        // Load search index
        const searchResponse = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            get_form_data: "get_form_data",
            form: "modules",
            project: "module_store",
          })
        );

        this.searchIndex = lunr(function () {
          this.ref("ref");
          this.field("name");

          searchResponse.data.forEach((doc) => {
            this.add({ ref: doc.ref, name: doc.name });
          });
        });

        // Load all modules
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            get_form_data: "get_form_data",
            form: "modules",
            project: "module_store",
          })
        );

        response.data.map((module) => {
          module.progress = 0;
          let block = false;
          if (!block) {
            module.status = "not_installed";
          }
          this.installedModules.forEach((installedModule) => {
            if (module.display_name == installedModule) {
              module.status = "installed";
              block = true;
            }
          });
        });

        this.modules = response.data;
      } catch (error) {
        console.error('Error loading modules:', error);
      } finally {
        this.loading = false;
      }
    },
    refreshModules() {
      this.installedModules = [];
      this.loadModules();
    },
    handleInput(event) {
      this.keyword = event.target.value;
    },
    setFilter(filter) {
      this.selectedFilter = filter;
    },
    installModule(module) {
      // Find the actual index in the modules array
      const moduleIndex = this.modules.findIndex(m => m.ref === module.ref);
      if (moduleIndex === -1) return;

      this.$axios.post(
        "install.php",
        this.$qs.stringify({
          install: "install",
          moduleID: module.id || module.red,
          project: this.$route.params.project,
        })
      );
      
      this.modules[moduleIndex].status = "installing";
      
      const intervalId = setInterval(() => {
        if (this.modules[moduleIndex].progress < 100) {
          this.modules[moduleIndex].progress = this.modules[moduleIndex].progress + 4;
        } else {
          this.modules[moduleIndex].status = "installed";
          clearInterval(intervalId);
        }
      }, 480);
    },
    uninstallModule(module) {
      // Find the actual index in the modules array
      const moduleIndex = this.modules.findIndex(m => m.ref === module.ref);
      if (moduleIndex === -1) return;

      this.$axios
        .post(
          "install.php",
          this.$qs.stringify({
            deinstall: "deinstall",
            moduleID: module.ref || module.id,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.modules[moduleIndex].status = "not_installed";
          this.modules[moduleIndex].progress = 0;
        });
    },
    getModuleCardClass(module) {
      return {
        'module-installed': module.status === 'installed',
        'module-installing': module.status === 'installing',
        'module-available': module.status === 'not_installed'
      };
    },
    getStatusClass(status) {
      switch (status) {
        case 'installed': return 'status-installed';
        case 'installing': return 'status-installing';
        case 'not_installed': return 'status-available';
        default: return 'status-unknown';
      }
    },
    getStatusIcon(status) {
      switch (status) {
        case 'installed': return 'checkmark-circle-outline';
        case 'installing': return 'sync-outline';
        case 'not_installed': return 'download-outline';
        default: return 'help-circle-outline';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'installed': return 'Installed';
        case 'installing': return 'Installing';
        case 'not_installed': return 'Available';
        default: return 'Unknown';
      }
    }
  },
};
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
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn.danger:hover:not(:disabled) {
  background: #b91c1c;
  border-color: #b91c1c;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Search and Filter */
.search-filter-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  gap: 16px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 300px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
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
  box-sizing: border-box;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.filter-buttons {
  display: flex;
  gap: 8px;
}

.filter-btn {
  padding: 8px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.filter-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Modules Container */
.modules-container {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  padding: 24px;
}

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
  opacity: 0.5;
}

.no-data-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-state p {
  margin: 0;
  font-size: 14px;
}

/* Modules Grid */
.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.module-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
  border-left: 4px solid var(--border);
}

.module-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.module-card.module-installed {
  border-left: 4px solid var(--success-color);
  background: #f0fdf4;
}

.module-card.module-installing {
  border-left: 4px solid var(--warning-color);
  background: #fefce8;
}

.module-card.module-available {
  border-left: 4px solid var(--primary-color);
}

.module-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.module-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  flex-shrink: 0;
}

.module-info {
  flex: 1;
  min-width: 0;
}

.module-name {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.2;
}

.module-description {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.module-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
}

.status-installed {
  background: #f0fdf4;
  color: var(--success-color);
}

.status-installing {
  background: #fef3c7;
  color: var(--warning-color);
}

.status-available {
  background: #eff6ff;
  color: var(--primary-color);
}

.status-unknown {
  background: #f1f5f9;
  color: var(--text-muted);
}

/* Progress Bar */
.progress-container {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
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
  background: var(--primary-color);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
  min-width: 35px;
}

/* Module Actions */
.module-actions {
  display: flex;
  justify-content: flex-end;
}

.spinning {
  animation: spin 1s linear infinite;
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }

  .module-card.module-installed {
    background: rgba(5, 150, 105, 0.1);
  }

  .module-card.module-installing {
    background: rgba(217, 119, 6, 0.1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .search-filter-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    min-width: 100%;
  }
  
  .filter-buttons {
    justify-content: center;
  }
  
  .modules-grid {
    grid-template-columns: 1fr;
  }
  
  .module-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .module-status {
    align-self: flex-start;
  }
}
</style>
