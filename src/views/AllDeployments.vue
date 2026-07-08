<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="rocket-outline" title="Deployments" />
      <div class="page-container">
        <PageHeader icon="rocket-outline" title="Deployments">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="loadDeployments" :disabled="loading">
              Refresh
            </ActionButton>
          </template>
        </PageHeader>

        <DataCard v-if="loading">
          <LoadingState message="Loading deployments..." icon="hourglass-outline" />
        </DataCard>

        <DataCard v-else-if="deployments.length === 0">
          <EmptyState icon="rocket-outline" title="No deployments yet"
            description="Codespace deployments will appear here once they run." />
        </DataCard>

        <DataCard v-else v-for="group in groupedByProject" :key="group.key"
          :title="group.project.name || 'Unassigned'"
          :subtitle="group.deployments.length + ' deployment' + (group.deployments.length === 1 ? '' : 's')" noPadding>
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
        </DataCard>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import SiteTitle from "@/components/SiteTitle.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import ActionButton from "@/components/ActionButton.vue";

export default defineComponent({
  name: 'AllDeployments',
  components: { SiteTitle, PageHeader, DataCard, LoadingState, EmptyState, ActionButton },
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
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

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .modern-table {
    min-width: 600px;
  }
}
</style>
