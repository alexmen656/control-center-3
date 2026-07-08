<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="server-outline" title="Database Tables" />

      <div class="page-container">
        <PageHeader icon="server-outline" title="Database Management">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="refreshTables">Refresh</ActionButton>
            <ActionButton variant="secondary" icon="search-outline" @click="toggleSearch">{{ showSearch ? 'Hide Search'
              : 'Search' }}</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="server-outline" color="primary" :value="tables.length" label="Total Tables" />
          <StatCard icon="search-outline" color="info" :value="filteredTables.length" label="Filtered Results" />
          <StatCard icon="grid-outline" color="success" :value="search ? 'Active' : 'Inactive'" label="Search Filter" />
        </div>

        <div v-if="showSearch" class="search-container">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input type="text" placeholder="Search tables..." v-model="search" class="search-input" autofocus>
            <button v-if="search" @click="search = ''" class="clear-search">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
        </div>

        <DataCard title="Tables" :subtitle="`${filteredTables.length} table${filteredTables.length !== 1 ? 's' : ''}`"
          noPadding>
          <div class="table-wrapper">
            <LoadingState v-if="tables.length === 0" message="Loading tables..." />

            <EmptyState v-else-if="filteredTables.length === 0" icon="server-outline" title="No Tables Found"
              :description="search ? 'No tables match your search criteria.' : 'No database tables available.'">
              <ActionButton v-if="search" variant="primary" @click="search = ''">Clear Search</ActionButton>
            </EmptyState>

            <div v-else class="tables-grid">
              <div v-for="table in filteredTables" :key="table[0]" class="table-card" @click="openTable(table[0])">
                <div class="table-icon">
                  <ion-icon name="grid-outline"></ion-icon>
                </div>
                <div class="table-info">
                  <h4 v-if="search && search.length > 0" v-html="highlightMatch(table[0], search)"></h4>
                  <h4 v-else>{{ table[0] }}</h4>
                  <p>Database Table</p>
                </div>
                <div class="table-actions">
                  <ion-icon name="chevron-forward-outline"></ion-icon>
                </div>
              </div>
            </div>
          </div>
        </DataCard>

        <div class="quick-actions">
          <div class="quick-action-card">
            <div class="quick-action-icon">
              <ion-icon name="add-outline"></ion-icon>
            </div>
            <div class="quick-action-content">
              <h4>Create New Table</h4>
              <p>Design and create a new database table</p>
            </div>
          </div>
          <div class="quick-action-card">
            <div class="quick-action-icon">
              <ion-icon name="cloud-upload-outline"></ion-icon>
            </div>
            <div class="quick-action-content">
              <h4>Import Data</h4>
              <p>Import data from CSV or JSON files</p>
            </div>
          </div>
          <div class="quick-action-card">
            <div class="quick-action-icon">
              <ion-icon name="settings-outline"></ion-icon>
            </div>
            <div class="quick-action-content">
              <h4>Database Settings</h4>
              <p>Configure database connections and settings</p>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, getCurrentInstance, computed, onMounted, onUnmounted } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import ActionButton from "@/components/ActionButton.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";

export default defineComponent({
  name: "DatabasesView",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    DataCard,
    ActionButton,
    LoadingState,
    EmptyState,
  },
  data() {
    return {
      labels: ["Table Name"],
    };
  },
  setup() {
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const tables = ref([]);
    const search = ref("");
    const showSearch = ref(false);

    const loadTables = async () => {
      try {
        const response = await axios.post(
          "mysql.php",
          qs.stringify({ getTables: "getTables" }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );
        tables.value = response.data;
      } catch (error) {
        console.error('Error loading tables:', error);
      }
    };

    loadTables();

    const filteredTables = computed(() => {
      if (!search.value) return tables.value;
      return tables.value.filter((tr) => {
        // tr is likely an array, so check all cells
        return tr.some((td) => String(td).toLowerCase().includes(search.value.toLowerCase()));
      });
    });

    function handleKeydown(e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
        e.preventDefault();
        if (!showSearch.value) {
          toggleSearch();
        }
      }
    }

    onMounted(() => {
      if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleKeydown);
      }
    });

    onUnmounted(() => {
      if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeydown);
      }
    });

    function toggleSearch() {
      showSearch.value = !showSearch.value;
      if (!showSearch.value) search.value = "";
    }

    function highlightMatch(text, search) {
      if (!search) return text;
      // Properly escape special regex characters
      const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
      return String(text).replace(regex, '<mark class="highlight">$1</mark>');
    }

    function refreshTables() {
      loadTables();
    }

    function openTable(tableName) {
      // Navigate to table detail page
      window.location.href = `/databases/table/${tableName}`;
    }

    return {
      tables,
      search,
      filteredTables,
      showSearch,
      toggleSearch,
      highlightMatch,
      refreshTables,
      openTable,
    };
  },
});
</script>
<style scoped>
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.search-container {
  margin-bottom: 24px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 500px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-shadow: var(--shadow);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.clear-search {
  position: absolute;
  right: 8px;
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.clear-search:hover {
  background: var(--background);
  color: var(--text-primary);
}

.table-wrapper {
  overflow-x: auto;
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
  padding: 24px;
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--shadow);
}

.table-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.table-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  flex-shrink: 0;
}

.table-info {
  flex: 1;
}

.table-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.table-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.table-actions {
  color: var(--text-muted);
  font-size: 20px;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 24px;
}

.quick-action-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow);
}

.quick-action-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.quick-action-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 24px;
  flex-shrink: 0;
}

.quick-action-content h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.quick-action-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
}

.highlight {
  background: #ffe082;
  color: #d32f2f;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .tables-grid {
    grid-template-columns: 1fr;
    padding: 16px;
  }

  .quick-actions {
    grid-template-columns: 1fr;
  }

  .search-box {
    max-width: 100%;
  }
}
</style>
