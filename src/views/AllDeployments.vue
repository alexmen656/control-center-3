<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="rocket-outline" title="Deployments" />
      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <PageTitle icon="rocket-outline" title="Deployments" />
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="loadDeployments" :disabled="loading">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>
        </div>

        <div v-if="loading" class="data-card">
          <div class="loading-state">
            <ion-icon name="hourglass-outline" class="loading-icon"></ion-icon>
            <p>Loading deployments...</p>
          </div>
        </div>

        <div v-else-if="deployments.length === 0" class="data-card">
          <div class="no-data-state">
            <div class="no-data-content">
              <ion-icon name="rocket-outline" class="no-data-icon"></ion-icon>
              <h4>No deployments yet</h4>
              <p>Codespace deployments will appear here once they run.</p>
            </div>
          </div>
        </div>

        <div v-else class="data-card" v-for="group in groupedByProject" :key="group.key">
          <div class="card-header">
            <div class="header-left">
              <h3>
                <ion-icon :name="group.project.icon || 'folder-outline'" class="project-icon"></ion-icon>
                {{ group.project.name || 'Unassigned' }}
              </h3>
              <span class="entry-count">{{ group.deployments.length }} deployment{{ group.deployments.length === 1 ? ''
                : 's' }}</span>
            </div>
          </div>
          <div class="table-wrapper">
            <div class="modern-table">
              <div class="table-header">
                <div class="header-cell" style="flex: 0.9;"><span class="header-text">Status</span></div>
                <div class="header-cell" style="flex: 1.4;"><span class="header-text">Codespace</span></div>
                <div class="header-cell" style="flex: 2;"><span class="header-text">URL</span></div>
                <div class="header-cell" style="flex: 0.8;"><span class="header-text">Commit</span></div>
                <div class="header-cell" style="flex: 1.3;"><span class="header-text">Created</span></div>
              </div>
              <div class="table-body">
                <div v-for="d in group.deployments" :key="d.id" class="table-row">
                  <div class="table-cell" style="flex: 0.9;">
                    <span class="status-badge" :class="'status-' + d.status">
                      <ion-icon :name="statusIcon(d.status)"></ion-icon>
                      {{ d.status }}
                    </span>
                  </div>
                  <div class="table-cell" style="flex: 1.4;">
                    <div class="icon-cell">
                      <ion-icon :name="d.codespace.icon || 'code-outline'"></ion-icon>
                      <span class="cell-content">{{ d.codespace.name || d.codespace.slug }}</span>
                      <span class="runtime-tag">{{ d.runtime }}</span>
                    </div>
                  </div>
                  <div class="table-cell" style="flex: 2;">
                    <a v-if="d.url" class="url-link" :href="normalizeUrl(d.url)" target="_blank" rel="noopener">
                      <span class="cell-content">{{ d.url }}</span>
                      <ion-icon name="open-outline"></ion-icon>
                    </a>
                    <span v-else class="muted">—</span>
                  </div>
                  <div class="table-cell" style="flex: 0.8;">
                    <span v-if="d.commit_short" class="commit-sha">{{ d.commit_short }}</span>
                    <span v-else class="muted">—</span>
                  </div>
                  <div class="table-cell" style="flex: 1.3;">
                    <div class="icon-cell">
                      <ion-icon name="time-outline"></ion-icon>
                      <span class="cell-content">{{ formatDate(d.created_at) }}</span>
                    </div>
                  </div>
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
import { defineComponent } from 'vue';
import PageTitle from "@/components/PageTitle.vue";
import SiteTitle from "@/components/SiteTitle.vue";

export default defineComponent({
  name: 'AllDeployments',
  components: { PageTitle, SiteTitle },
  data() {
    return {
      loading: true,
      deployments: [],
    };
  },
  computed: {
    groupedByProject() {
      const groups = {};
      for (const d of this.deployments) {
        const key = d.project.id || 'unassigned';
        if (!groups[key]) {
          groups[key] = { key, project: d.project, deployments: [] };
        }
        groups[key].deployments.push(d);
      }
      return Object.values(groups);
    },
  },
  methods: {
    async loadDeployments() {
      this.loading = true;
      try {
        const response = await this.$axios.get('v2/deployments/');
        this.deployments = response.data.deployments || [];
      } catch (error) {
        console.error('Failed to load deployments:', error);
        this.deployments = [];
      } finally {
        this.loading = false;
      }
    },
    statusIcon(status) {
      switch (status) {
        case 'ready': return 'checkmark-circle-outline';
        case 'building': return 'sync-outline';
        case 'queued': return 'time-outline';
        case 'error': return 'alert-circle-outline';
        case 'canceled': return 'close-circle-outline';
        default: return 'ellipse-outline';
      }
    },
    normalizeUrl(url) {
      if (!url) return url;
      return url.startsWith('http') ? url : 'https://' + url;
    },
    formatDate(value) {
      if (!value) return '';
      const d = new Date(value.replace(' ', 'T'));
      if (isNaN(d.getTime())) return value;
      return d.toLocaleString();
    },
  },
  mounted() {
    this.loadDeployments();
  },
});
</script>

<style scoped>
.modern-content {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --info-color: #0891b2;
  --accent-color: #7c3aed;
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
  background: var(--background);
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content {
  flex: 1;
  min-width: 300px;
}

.page-subtitle {
  margin: 8px 0 0;
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

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn:disabled {
  opacity: 0.6;
  cursor: default;
}

.action-btn ion-icon {
  font-size: 16px;
}

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
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(135deg, var(--background), var(--surface));
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.project-icon {
  color: var(--primary-color);
  font-size: 20px;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 13px;
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 800px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 100px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:last-child {
  border-bottom: none;
}

.table-row:hover {
  background: var(--background);
}

.table-cell {
  flex: 1;
  min-width: 100px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.icon-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.icon-cell ion-icon {
  color: var(--text-muted);
  font-size: 16px;
  flex-shrink: 0;
}

.runtime-tag {
  font-size: 10px;
  font-weight: 600;
  color: var(--text-secondary);
  background: var(--background);
  border: 1px solid var(--border);
  padding: 1px 6px;
  border-radius: 6px;
  text-transform: uppercase;
  flex-shrink: 0;
}

.url-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--primary-color);
  text-decoration: none;
  min-width: 0;
}

.url-link:hover {
  text-decoration: underline;
}

.url-link ion-icon {
  flex-shrink: 0;
  font-size: 14px;
}

.commit-sha {
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 12px;
  background: var(--background);
  border: 1px solid var(--border);
  padding: 2px 8px;
  border-radius: var(--radius);
  color: var(--text-primary);
}

.muted {
  color: var(--text-muted);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge ion-icon {
  font-size: 14px;
}

.status-ready {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-building {
  background: rgba(8, 145, 178, 0.1);
  color: var(--info-color);
  border: 1px solid rgba(8, 145, 178, 0.2);
}

.status-queued {
  background: rgba(100, 116, 139, 0.1);
  color: var(--secondary-color);
  border: 1px solid rgba(100, 116, 139, 0.2);
}

.status-error {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.status-canceled {
  background: rgba(100, 116, 139, 0.1);
  color: var(--secondary-color);
  border: 1px solid rgba(100, 116, 139, 0.2);
}

.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon,
.no-data-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
  opacity: 0.5;
}

.loading-icon {
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

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .modern-table {
    min-width: 600px;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }
}
</style>
