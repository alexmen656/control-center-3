<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="server-outline" title="Manage Tables" />

      <div class="page-container">
        <PageHeader icon="server-outline" title="Table Management">
          <template #actions>
            <ActionButton icon="refresh-outline" @click="refreshTables">Refresh</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="layers-outline" color="primary" :value="tables.length" label="Total Tables" />
          <StatCard icon="document-text-outline" color="info" :value="totalRows" label="Total Records" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="activeTables" label="Active Tables" />
          <StatCard icon="alert-circle-outline" color="warning" :value="inactiveTables" label="Missing Tables" />
        </div>

        <DataCard title="Tables">
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search tables..." />
          </template>

          <LoadingState v-if="loading" message="Loading tables..." />

          <EmptyState v-else-if="filteredTables.length === 0" icon="layers-outline" title="No Tables Found"
            :description="searchTerm ? 'No tables match your search criteria.' : 'No tables exist for this project yet.'" />

          <div v-else class="tables-grid">
            <div v-for="table in filteredTables" :key="table.name" class="table-card" :class="{
              'table-active': table.exists,
              'table-inactive': !table.exists
            }">
              <div class="table-header">
                <div class="table-status">
                  <ion-icon :name="table.exists ? 'checkmark-circle' : 'alert-circle'"
                    :class="table.exists ? 'status-active' : 'status-inactive'"></ion-icon>
                  <span class="table-name">{{ table.name }}</span>
                </div>
                <div class="table-actions">
                  <button class="icon-btn view-btn" @click="viewTable(table.name)" :disabled="!table.exists"
                    title="View Data">
                    <ion-icon name="eye-outline"></ion-icon>
                  </button>
                  <button class="icon-btn edit-btn" @click="editForm(table.name)" title="Edit Table">
                    <ion-icon name="create-outline"></ion-icon>
                  </button>
                  <button class="icon-btn delete-btn" @click="confirmDelete(table)" title="Delete Table">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>

              <div class="table-details">
                <div class="detail-item">
                  <span class="detail-label">Records:</span>
                  <span class="detail-value">{{ table.row_count.toLocaleString() }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Fields:</span>
                  <span class="detail-value">{{ table.field_count }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Created:</span>
                  <span class="detail-value">{{ formatDate(table.created_at) }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Table:</span>
                  <span class="detail-value table-name-code">{{ table.table_name }}</span>
                </div>
              </div>

              <div v-if="!table.exists" class="table-warning">
                <ion-icon name="warning-outline"></ion-icon>
                <span>Database table missing - table exists but no data table found</span>
              </div>
            </div>
          </div>
        </DataCard>
      </div>

      <AppModal v-model="deleteModal.show" title="Delete Table">
        <div class="warning-content">
          <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
          <h4>Are you sure?</h4>
          <p>This will permanently delete the table <strong>"{{ deleteModal.table?.name }}"</strong> and all its data
            ({{
              deleteModal.table?.row_count }} records).</p>
          <p class="warning-text">This action cannot be undone!</p>
        </div>
        <template #footer>
          <ActionButton @click="deleteModal.show = false">Cancel</ActionButton>
          <ActionButton variant="danger" @click="deleteTable()">Delete Permanently</ActionButton>
        </template>
      </AppModal>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import DataCard from "@/components/DataCard.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import AppModal from "@/components/AppModal.vue";
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageTables",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    ActionButton,
    DataCard,
    SearchBox,
    LoadingState,
    EmptyState,
    AppModal,
  },
  data() {
    return {
      tables: [],
      loading: true,
      searchTerm: '',
      deleteModal: {
        show: false,
        table: null
      }
    };
  },
  computed: {
    filteredTables() {
      if (!this.searchTerm.trim()) {
        return this.tables;
      }

      const searchLower = this.searchTerm.toLowerCase();
      return this.tables.filter(table =>
        table.name.toLowerCase().includes(searchLower) ||
        table.table_name.toLowerCase().includes(searchLower)
      );
    },
    totalRows() {
      return this.tables.reduce((sum, table) => sum + Number(table.row_count), 0);
    },
    activeTables() {
      return this.tables.filter(table => table.exists).length;
    },
    inactiveTables() {
      return this.tables.filter(table => !table.exists).length;
    }
  },
  created() {
    this.loadTables();
  },
  methods: {
    async loadTables() {
      this.loading = true;
      try {
        const response = await this.$axios.post(
          "table.php",
          this.$qs.stringify({
            get_all_tables: "get_all_tables",
            project: this.$route.params.project,
          })
        );

        if (response.data.success) {
          this.tables = response.data.tables;
        } else {
          console.error('Error loading tables:', response.data.error);
          this.tables = [];
        }
      } catch (error) {
        console.error('Error loading tables:', error);
        this.tables = [];
      } finally {
        this.loading = false;
      }
    },
    refreshTables() {
      this.loadTables();
    },
    viewTable(tableName) {
      this.$router.push({
        path: `/project/${this.$route.params.project}/tables/${tableName}`
      });
    },
    editForm(tableName) {
      this.$router.push({
        path: `/project/${this.$route.params.project}/tables/${tableName}/edit`
      });
    },
    confirmDelete(table) {
      this.deleteModal.table = table;
      this.deleteModal.show = true;
    },
    async deleteTable() {
      if (!this.deleteModal.table) return;

      try {
        const response = await this.$axios.post(
          "table.php",
          this.$qs.stringify({
            drop_table: "drop_table",
            table_name: this.deleteModal.table.name,
            project: this.$route.params.project,
          })
        );

        if (response.data.success) {
          // Remove from local array
          this.tables = this.tables.filter(t => t.name !== this.deleteModal.table.name);
          this.deleteModal.show = false;
          this.deleteModal.table = null;

          // Emit sidebar refresh
          this.emitter.emit("updateSidebar");
        } else {
          alert('Error deleting table: ' + response.data.error);
        }
      } catch (error) {
        console.error('Error deleting table:', error);
        alert('Error deleting table');
      }
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
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
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
}

.table-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.table-card.table-active {
  border-left: 4px solid var(--success-color);
}

.table-card.table-inactive {
  border-left: 4px solid var(--warning-color);
  background: #fefce8;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.table-status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-name {
  font-weight: 600;
  font-size: 16px;
  color: var(--text-primary);
}

.status-active {
  color: var(--success-color);
  font-size: 18px;
}

.status-inactive {
  color: var(--warning-color);
  font-size: 18px;
}

.table-actions {
  display: flex;
  gap: 8px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.view-btn {
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.view-btn:hover:not(:disabled) {
  background: rgba(249, 115, 22, 0.22);
}

.edit-btn {
  background: rgba(45, 211, 111, 0.12);
  color: var(--success-color);
}

.edit-btn:hover {
  background: rgba(45, 211, 111, 0.22);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
}

.table-details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.detail-label {
  color: var(--text-secondary);
  font-weight: 500;
}

.detail-value {
  color: var(--text-primary);
  font-weight: 600;
}

.table-name-code {
  font-family: monospace;
  font-size: 12px;
  color: var(--text-muted);
  font-weight: normal;
}

.table-warning {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: var(--radius);
  color: #92400e;
  font-size: 12px;
  margin-top: 12px;
}

.table-warning ion-icon {
  font-size: 16px;
  color: #f59e0b;
}

.warning-content {
  text-align: center;
  margin-bottom: 24px;
}

.warning-icon {
  font-size: 48px;
  color: var(--warning-color);
  margin-bottom: 16px;
}

.warning-content h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.warning-content p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

.warning-text {
  color: var(--danger-color);
  font-weight: 600;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .tables-grid {
    grid-template-columns: 1fr;
  }
}
</style>
