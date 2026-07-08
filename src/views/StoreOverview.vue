<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="storefront-outline" title="Module Store" />

      <div class="page-container">
        <PageHeader icon="storefront-outline" title="Module Store">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="loadModules">Refresh</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="cube-outline" color="primary" :value="modules.length" label="Total Modules" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="addedCount" label="Added" />
          <StatCard icon="download-outline" color="info" :value="availableCount" label="Available" />
        </div>

        <DataCard title="Available Modules"
          :subtitle="filteredModules.length + ' module' + (filteredModules.length !== 1 ? 's' : '')">
          <template #actions>
            <SearchBox v-model="keyword" placeholder="Search modules..." @update:modelValue="handleSearch" />
          </template>

          <LoadingState v-if="loading" message="Loading modules..." />

          <EmptyState v-else-if="filteredModules.length === 0" icon="cube-outline" title="No Modules Found"
            :description="keyword ? 'No modules match your search.' : 'No modules available.'" />

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

              <div v-if="module.status === 'adding'" class="progress-wrapper">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: module.progress + '%' }"></div>
                </div>
                <span class="progress-text">{{ module.progress }}%</span>
              </div>

              <div class="module-actions">
                <ActionButton v-if="module.status === 'available'" variant="primary" icon="add-outline"
                  @click="addModule(module)">Add Module</ActionButton>
                <button v-else-if="module.status === 'added'" class="action-btn success" @click="removeModule(module)">
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
        </DataCard>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import lunr from "lunr";
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import DataCard from "@/components/DataCard.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";

export default {
  name: "StoreOverview",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    ActionButton,
    DataCard,
    SearchBox,
    LoadingState,
    EmptyState,
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
}

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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

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

.module-actions {
  display: flex;
  gap: 8px;
}

.module-actions .action-btn {
  width: 100%;
  justify-content: center;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-color-scheme: dark) {
  .module-card.is-added {
    background: rgba(5, 150, 105, 0.12);
  }

  .module-card.is-adding {
    background: rgba(217, 119, 6, 0.12);
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
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
