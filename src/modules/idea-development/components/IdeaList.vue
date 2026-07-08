<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="bulb-outline" title="Ideen Entwicklung" />

      <div class="page-container">
        <PageHeader icon="bulb-outline" title="Ideen Entwicklung">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="loadIdeas">Refresh</ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="createIdea">Neue Idee</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="bulb-outline" color="primary" :value="ideas.length" label="Gesamt Ideen" />
          <StatCard icon="time-outline" color="warning" :value="ideas.filter(i => i.status === 'in_progress').length" label="In Bearbeitung" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="ideas.filter(i => i.status === 'completed').length" label="Abgeschlossen" />
          <StatCard icon="document-text-outline" color="info" :value="ideas.filter(i => i.status === 'draft').length" label="Entwürfe" />
        </div>

        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Alle Ideen</h3>
              <span class="entry-count">{{ filteredIdeas.length }} Ideen</span>
            </div>
            <SearchBox v-model="searchTerm" placeholder="Suche..." />
          </div>

          <div class="table-wrapper">
            <LoadingState v-if="loading" message="Lade Ideen..." />

            <EmptyState v-else-if="filteredIdeas.length === 0" icon="bulb-outline" title="Keine Ideen gefunden" description="Erstelle eine neue Idee um zu starten." />

            <div v-else class="modern-table">
              <div class="table-header">
                <div class="header-cell">Titel</div>
                <div class="header-cell">Progress</div>
                <div class="header-cell">Status</div>
                <div class="header-cell">Erstellt am</div>
                <div class="header-cell actions-header">Aktionen</div>
              </div>

              <div class="table-body">
                <div v-for="idea in filteredIdeas" :key="idea.id" class="table-row" @click="openIdea(idea)">
                  <div class="table-cell cell-name">
                    <div class="user-info">
                      <span class="user-name">{{ idea.title }}</span>
                      <span class="user-email">{{ truncate(idea.description, 50) }}</span>
                    </div>
                  </div>

                  <div class="table-cell">
                    <div class="progress-bar-container" v-if="idea.milestones && idea.milestones.length > 0">
                      <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: getProgress(idea) + '%' }"></div>
                      </div>
                      <span class="progress-text">{{ getProgress(idea) }}%</span>
                    </div>
                    <span v-else class="text-muted">Keine Meilensteine</span>
                  </div>

                  <div class="table-cell">
                    <span class="status-badge" :class="getStatusClass(idea.status)">
                      {{ getStatusLabel(idea.status) }}
                    </span>
                  </div>

                  <div class="table-cell">
                    <span>{{ formatDate(idea.created_at) }}</span>
                  </div>

                  <div class="table-cell cell-actions">
                     <button class="icon-btn" @click.stop="openIdea(idea)">
                        <ion-icon name="create-outline"></ion-icon>
                     </button>
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

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent, IonIcon } from '@ionic/vue';
import { 
  addOutline, refreshOutline, bulbOutline, 
  timeOutline, checkmarkCircleOutline, documentTextOutline,
  searchOutline, syncOutline, createOutline
} from 'ionicons/icons';
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import { ideaService, type Idea } from '../services/IdeaService';

export default defineComponent({
  name: 'IdeaList',
  components: {
    IonPage, IonContent, IonIcon, SiteTitle, StatCard,
    PageHeader, ActionButton, SearchBox, LoadingState, EmptyState
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const project = route.params.project as string;
    const ideas = ref<Idea[]>([]);
    const loading = ref(false);
    const searchTerm = ref('');

    const loadIdeas = async () => {
      loading.value = true;
      try {
        const result = await ideaService.getIdeas(project);
        ideas.value = Array.isArray(result) ? result : [];
      } catch (error) {
        console.error('Failed to load ideas', error);
        ideas.value = [];
      } finally {
        loading.value = false;
      }
    };

    const filteredIdeas = computed(() => {
      if (!searchTerm.value) return ideas.value;
      const term = searchTerm.value.toLowerCase();
      return ideas.value.filter(idea => 
        idea.title.toLowerCase().includes(term) || 
        (idea.description && idea.description.toLowerCase().includes(term))
      );
    });

    const openIdea = (idea: Idea) => {
      if(idea && idea.id) {
        router.push({ name: 'idea-detail', params: { project, id: idea.id } });
      }
    };

    const createIdea = async () => {
        try {
            loading.value = true;
            const newIdea = await ideaService.createIdea(project, {
                title: 'Neue Idee',
                status: 'draft',
                milestones: [],
                notes: '# Meine neue Idee\n\nBeschreibe deine Idee hier...',
                assets: []
            });
            
            if (newIdea && newIdea.id) {
                await loadIdeas();
                openIdea(newIdea);
            } else {
                console.error("Failed to create idea: No ID returned");
            }
        } catch (e) {
            console.error(e);
        } finally {
            loading.value = false;
        }
    };

    const getStatusLabel = (status: string) => {
        const map: Record<string, string> = {
            'draft': 'Entwurf',
            'in_progress': 'In Bearbeitung',
            'completed': 'Abgeschlossen',
            'archived': 'Archiviert'
        };
        return map[status] || status;
    };

    const getStatusClass = (status: string) => {
        const map: Record<string, string> = {
            'draft': 'status-pending',
            'in_progress': 'status-active',
            'completed': 'status-success',
            'archived': 'status-inactive'
        };
        return map[status] || '';
    };

    const getProgress = (idea: Idea) => {
        if (!idea.milestones || idea.milestones.length === 0) return 0;
        const completed = idea.milestones.filter(m => m.isCompleted).length;
        return Math.round((completed / idea.milestones.length) * 100);
    };
    
    const formatDate = (dateStr?: string) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('de-DE');
    };

    const truncate = (text: string | undefined, length: number) => {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    };

    onMounted(loadIdeas);

    return { 
      ideas, filteredIdeas, loading, searchTerm,
      loadIdeas, openIdea, createIdea, 
      getStatusLabel, getStatusClass, getProgress, formatDate, truncate,
      addOutline, refreshOutline, bulbOutline, timeOutline, checkmarkCircleOutline, 
      documentTextOutline, searchOutline, syncOutline, createOutline
    };
  }
});
</script>

<style scoped>
.modern-content {
  --background: #f8f9fa;
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.data-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.header-left h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.entry-count {
  color: var(--ion-color-medium);
  font-size: 14px;
}

.modern-table {
  width: 100%;
}

.table-header {
  display: flex;
  padding: 12px 24px;
  background: #f8f9fa;
  font-weight: 600;
  color: var(--ion-color-medium);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.header-cell {
  flex: 1;
}

.table-row {
  display: flex;
  padding: 16px 24px;
  align-items: center;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.2s;
}

.table-row:hover {
  background: #fafafa;
}

.table-cell {
  flex: 1;
  font-size: 14px;
  color: var(--ion-text-color);
}

.cell-name {
  flex: 2;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: var(--ion-text-color);
}

.user-email {
  font-size: 13px;
  color: var(--ion-color-medium);
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.status-active {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary);
}

.status-pending {
  background: var(--ion-color-warning-tint);
  color: var(--ion-color-warning-shade);
}

.status-success {
    background: var(--ion-color-success-tint);
    color: var(--ion-color-success);
}

.status-inactive {
  background: var(--ion-color-medium-tint);
  color: var(--ion-color-medium);
}

.progress-bar-container {
    width: 90%;
    display: flex;
    align-items: center;
    gap: 8px;
}
.progress-bar {
    flex: 1;
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: var(--ion-color-primary);
}
.progress-text {
    font-size: 12px;
    color: var(--ion-color-medium);
    width: 30px;
}
.text-muted {
    color: var(--ion-color-medium);
    font-style: italic;
    font-size: 12px;
}

.icon-btn {
    background: none;
    border: none;
    font-size: 18px;
    color: var(--ion-color-medium);
    cursor: pointer;
    padding: 4px;
}
.icon-btn:hover {
    color: var(--ion-color-primary);
}

</style>
