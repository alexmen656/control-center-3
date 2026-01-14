<template>
  <ion-page>
    <ion-content class="add-module-content">
      <SiteTitle icon="add-circle-outline" title="Add Module" />

      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <h1>Add Module to Project</h1>
          <p>Select a module from the list below to add it to your project</p>
        </div>

        <!-- Search -->
        <div class="search-container">
          <ion-icon name="search-outline" class="search-icon"></ion-icon>
          <input type="text" class="search-input" placeholder="Search modules..." v-model="keyword"
            @input="handleSearch">
        </div>

        <!-- Modules List -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Loading available modules...</p>
        </div>

        <div v-else-if="displayModules.length === 0" class="empty-state">
          <ion-icon name="cube-outline" class="empty-icon"></ion-icon>
          <h3>No Modules Found</h3>
          <p>{{ keyword ? 'No modules match your search.' : 'No modules are available.' }}</p>
        </div>

        <div v-else class="modules-list">
          <div v-for="module in displayModules" :key="module.ref" class="module-item"
            :class="{ 'module-added': module.status === 'installed', 'module-adding': module.status === 'installing' }">
            <div class="module-content">
              <div class="module-icon-wrapper">
                <img v-if="module.icon" :src="module.icon" class="module-icon" />
                <ion-icon v-else name="cube-outline" class="module-icon"></ion-icon>
              </div>
              <div class="module-details">
                <h3 class="module-title">{{ module.display_name || module.name }}</h3>
                <p class="module-desc">{{ module.description || 'No description available' }}</p>

                <!-- Progress for installing -->
                <div v-if="module.status === 'installing'" class="progress-bar">
                  <div class="progress-fill" :style="{ width: module.progress + '%' }"></div>
                </div>
              </div>
            </div>

            <div class="module-action">
              <button v-if="module.status === 'not_installed'" class="btn-add" @click="addModule(module)">
                <ion-icon name="add-outline"></ion-icon>
                Add
              </button>
              <button v-else-if="module.status === 'installed'" class="btn-remove" @click="removeModule(module)">
                <ion-icon name="checkmark-outline"></ion-icon>
                Added
              </button>
              <button v-else-if="module.status === 'installing'" class="btn-adding" disabled>
                <ion-icon name="sync-outline" class="spinning"></ion-icon>
                Adding...
              </button>
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
  name: "AddModule",
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
      installedModuleNames: [],
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
    }
  },
  async created() {
    await this.loadModules();
  },
  methods: {
    async loadModules() {
      this.loading = true;

      try {
        // Load installed tools
        const installedResponse = await this.$axios.post(
          "tools.php",
          this.$qs.stringify({
            getProjectTools: "getProjectTools",
            project: this.$route.params.project,
          })
        );

        if (installedResponse.data) {
          this.installedModuleNames = installedResponse.data.map(tool => tool.name);
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
            status: this.installedModuleNames.includes(module.display_name) ? 'installed' : 'not_installed'
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

      this.modules[moduleIndex].status = "installing";

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
              this.modules[moduleIndex].progress += 4;
            } else {
              this.modules[moduleIndex].status = "installed";
              this.modules[moduleIndex].progress = 0;
              this.emitter.emit("updateSidebar");
              clearInterval(intervalId);
            }
          }, 480);
        } else {
          this.modules[moduleIndex].status = "not_installed";
          this.modules[moduleIndex].progress = 0;
          alert("Failed to add module: " + response.data);
        }
      }).catch(() => {
        this.modules[moduleIndex].status = "not_installed";
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
            this.modules[moduleIndex].status = "not_installed";
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
.add-module-content {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --success: #059669;
  --success-light: #d1fae5;
  --danger: #dc2626;
  --warning: #f59e0b;
  --warning-light: #fef3c7;
  --radius: 8px;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.page-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}

/* Header */
.page-header {
  margin-bottom: 32px;
  text-align: center;
}

.page-header h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
}

.page-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Search */
.search-container {
  position: relative;
  margin-bottom: 24px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 20px;
  pointer-events: none;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 14px 16px 14px 48px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 15px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Loading & Empty States */
.loading-state,
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.empty-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.empty-state p {
  margin: 0;
  font-size: 15px;
}

/* Modules List */
.modules-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.module-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
  gap: 16px;
}

.module-item:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-md);
}

.module-item.module-added {
  border-color: var(--success);
  background: var(--success-light);
}

.module-item.module-adding {
  border-color: var(--warning);
  background: var(--warning-light);
}

.module-content {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
  min-width: 0;
}

.module-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
}

.module-icon {
  width: 32px;
  height: 32px;
  color: white;
  font-size: 32px;
  object-fit: contain;
}

.module-details {
  flex: 1;
  min-width: 0;
}

.module-title {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
}

.module-desc {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Progress Bar */
.progress-bar {
  margin-top: 8px;
  height: 6px;
  background: var(--border);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Action Buttons */
.module-action {
  flex-shrink: 0;
}

.btn-add,
.btn-remove,
.btn-adding {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-add {
  background: var(--primary);
  color: white;
}

.btn-add:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-remove {
  background: var(--success);
  color: white;
}

.btn-remove:hover {
  background: var(--danger);
}

.btn-adding {
  background: var(--warning);
  color: white;
  opacity: 0.8;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .add-module-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .module-item.module-added {
    background: rgba(5, 150, 105, 0.15);
  }

  .module-item.module-adding {
    background: rgba(245, 158, 11, 0.15);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header h1 {
    font-size: 24px;
  }

  .module-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .module-action {
    width: 100%;
  }

  .btn-add,
  .btn-remove,
  .btn-adding {
    width: 100%;
    justify-content: center;
  }
}
</style>
