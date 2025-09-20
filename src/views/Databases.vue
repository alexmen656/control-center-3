<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="server-outline" title="Database Tables" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Database Management</h1>
            <p>Browse and manage your database tables</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshTables">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn secondary" @click="toggleSearch">
              <ion-icon name="search-outline"></ion-icon>
              {{ showSearch ? 'Hide Search' : 'Search' }}
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="server-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ tables.length }}</h3>
              <p>Total Tables</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="search-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ filteredTables.length }}</h3>
              <p>Filtered Results</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="grid-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ search ? 'Active' : 'Inactive' }}</h3>
              <p>Search Filter</p>
            </div>
          </div>
        </div>

        <!-- Search Bar -->
        <div v-if="showSearch" class="search-container">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input 
              type="text" 
              placeholder="Search tables..." 
              v-model="search"
              class="search-input"
              autofocus
            >
            <button v-if="search" @click="search = ''" class="clear-search">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
        </div>

        <!-- Tables Grid -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Database Tables</h3>
              <span class="entry-count">{{ filteredTables.length }} table{{ filteredTables.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="tables.length === 0" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading tables...</p>
            </div>

            <div v-else-if="filteredTables.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="server-outline" class="no-data-icon"></ion-icon>
                <h4>No Tables Found</h4>
                <p>{{ search ? 'No tables match your search criteria.' : 'No database tables available.' }}</p>
                <button v-if="search" @click="search = ''" class="action-btn primary">
                  Clear Search
                </button>
              </div>
            </div>

            <div v-else class="tables-grid">
              <div 
                v-for="table in filteredTables" 
                :key="table[0]"
                class="table-card"
                @click="openTable(table[0])"
              >
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
        </div>

        <!-- Quick Actions -->
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

export default defineComponent({
  name: "DatabasesView",
  components: {
    SiteTitle,
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
          qs.stringify({getTables: "getTables"}),
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
/* Modern Design System - Same as FormDisplay */
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
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
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
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  flex-shrink: 0;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Search Container */
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

/* Action Buttons */
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

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
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

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Tables Grid */
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

/* Quick Actions */
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
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);
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

/* Highlight Match */
.highlight {
  background: #ffe082;
  color: #d32f2f;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
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

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}
</style>
