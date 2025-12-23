<template>
  <ion-page>
    <ion-content class="modern-content">

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Database Management</h1>
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

        <!-- Simple Stats Bar -->
        <div class="stats-bar">
          <div class="stat-item">
            <span class="stat-value">{{ tables.length }}</span>
            <span class="stat-label">Tables</span>
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
                <ion-icon name="server-outline" class="table-icon"></ion-icon>
                <div class="table-name">
                  <span v-if="search && search.length > 0" v-html="highlightMatch(table[0], search)"></span>
                  <span v-else>{{ table[0] }}</span>
                </div>
                <ion-icon name="chevron-forward-outline" class="chevron-icon"></ion-icon>
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

export default defineComponent({
  name: "DatabasesView",
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
/* Mobile-First Design System */
.modern-content {
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --radius: 12px;
}

.page-container {
  padding: 16px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  margin-bottom: 20px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.header-content p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 8px;
}

/* Stats Bar */
.stats-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  padding: 12px;
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.stat-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-label {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
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
  padding: 12px 16px 12px 44px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--surface);
  color: var(--text-primary);
  min-height: 44px;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.clear-search {
  position: absolute;
  right: 8px;
  background: none;
  border: none;
  color: var(--text-secondary);
  padding: 8px;
  border-radius: var(--radius);
  -webkit-tap-highlight-color: transparent;
  min-width: 44px;
  min-height: 44px;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  -webkit-tap-highlight-color: transparent;
  min-height: 44px;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn ion-icon {
  font-size: 18px;
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
  border-radius: var(--radius);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  -webkit-tap-highlight-color: transparent;
}

.table-card:active {
  background: var(--background);
}

.table-icon {
  font-size: 24px;
  color: var(--primary-color);
  flex-shrink: 0;
  width: 24px;
}

.table-name {
  flex: 1;
  min-width: 0;
  font-size: 15px;
  font-weight: 500;
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chevron-icon {
  font-size: 20px;
  color: var(--text-secondary);
  flex-shrink: 0;
}

/* Highlight Match */
.highlight {
  background: #ffe082;
  color: #d32f2f;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}
</style>
