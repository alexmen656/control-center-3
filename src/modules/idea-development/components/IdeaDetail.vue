<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="#"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ idea?.title || 'Laden...' }}</ion-title>
        <ion-buttons slot="end">
            <ion-button @click="saveIdea">
                <ion-icon :icon="save" slot="start"></ion-icon>
                Speichern
            </ion-button>
             <ion-button color="danger" @click="deleteDetail">
                <ion-icon :icon="trash"></ion-icon>
            </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
          <ion-segment v-model="segment">
              <ion-segment-button value="details">
                  <ion-label>Details</ion-label>
              </ion-segment-button>
              <ion-segment-button value="milestones">
                   <ion-label>Meilensteine</ion-label>
              </ion-segment-button>
              <ion-segment-button value="notes">
                   <ion-label>Notizen</ion-label>
              </ion-segment-button>
              <ion-segment-button value="assets">
                   <ion-label>Assets</ion-label>
              </ion-segment-button>
          </ion-segment>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
        <div v-if="!idea">Lade Idee...</div>
        <div v-else>
            <!-- Details -->
            <div v-show="segment === 'details'">
                <ion-item>
                    <ion-label position="stacked">Titel</ion-label>
                    <ion-input v-model="idea.title"></ion-input>
                </ion-item>
                 <ion-item>
                    <ion-label position="stacked">Status</ion-label>
                    <ion-select v-model="idea.status">
                        <ion-select-option value="draft">Entwurf</ion-select-option>
                        <ion-select-option value="in_progress">In Bearbeitung</ion-select-option>
                        <ion-select-option value="completed">Abgeschlossen</ion-select-option>
                        <ion-select-option value="archived">Archiviert</ion-select-option>
                    </ion-select>
                </ion-item>
                <ion-item>
                    <ion-label position="stacked">Beschreibung</ion-label>
                    <ion-textarea v-model="idea.description" rows="5"></ion-textarea>
                </ion-item>
            </div>

            <!-- Milestones -->
            <div v-show="segment === 'milestones'">
                <ion-list>
                    <ion-item>
                        <ion-input placeholder="Neuer Meilenstein" v-model="newMilestoneTitle"></ion-input>
                        <ion-button slot="end" @click="addMilestone">Hinzufügen</ion-button>
                    </ion-item>
                    <ion-item v-for="(ms, index) in idea.milestones" :key="index">
                        <ion-checkbox slot="start" v-model="ms.isCompleted"></ion-checkbox>
                        <ion-input v-model="ms.title"></ion-input>
                        <ion-button color="danger" slot="end" fill="clear" @click="removeMilestone(index)">
                            <ion-icon :icon="trash"></ion-icon>
                        </ion-button>
                    </ion-item>
                </ion-list>
            </div>

            <!-- Notes -->
            <div v-show="segment === 'notes'">
                <ion-item lines="none">
                    <ion-label>Notizen (Markdown)</ion-label>
                </ion-item>
                <ion-textarea v-model="idea.notes" rows="20" placeholder="Schreibe hier..."></ion-textarea>
            </div>

            <!-- Assets -->
            <div v-show="segment === 'assets'">
                 <ion-list>
                    <ion-item>
                         <ion-select v-model="newAssetType" placeholder="Typ" slot="start">
                             <ion-select-option value="link">Link</ion-select-option>
                             <ion-select-option value="image">Bild</ion-select-option>
                         </ion-select>
                         <ion-input placeholder="Name" v-model="newAssetName"></ion-input>
                    </ion-item>
                    <ion-item>
                        <ion-input placeholder="URL" v-model="newAssetUrl"></ion-input>
                        <ion-button slot="end" @click="addAsset">Hinzufügen</ion-button>
                    </ion-item>

                    <ion-item-divider>
                        <ion-label>Gespeicherte Assets</ion-label>
                    </ion-item-divider>

                    <ion-item v-for="(asset, index) in idea.assets" :key="index">
                        <ion-label>
                            <h3>{{ asset.name }}</h3>
                            <p>{{ asset.url }}</p>
                            <p>{{ asset.type }}</p>
                        </ion-label>
                         <ion-button :href="asset.url" target="_blank" slot="end" fill="outline">Öffnen</ion-button>
                         <ion-button color="danger" slot="end" fill="clear" @click="removeAsset(index)">
                            <ion-icon :icon="trash"></ion-icon>
                        </ion-button>
                    </ion-item>
                 </ion-list>
            </div>
        </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon, IonSegment, IonSegmentButton, IonLabel, IonItem, IonInput, IonTextarea, IonSelect, IonSelectOption, IonList, IonCheckbox, IonBackButton, IonItemDivider } from '@ionic/vue';
import { save, trash } from 'ionicons/icons';
import { ideaService, type Idea, type Milestone, type Asset } from '../services/IdeaService';

export default defineComponent({
  name: 'IdeaDetail',
  components: { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon, IonSegment, IonSegmentButton, IonLabel, IonItem, IonInput, IonTextarea, IonSelect, IonSelectOption, IonList, IonCheckbox, IonBackButton, IonItemDivider },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const project = route.params.project as string;
    const ideaId = parseInt(route.params.id as string);
    const idea = ref<Idea | null>(null);
    const segment = ref('details');

    // Milestones
    const newMilestoneTitle = ref('');

    // Assets
    const newAssetType = ref<'link' | 'image' | 'file'>('link');
    const newAssetName = ref('');
    const newAssetUrl = ref('');

    const loadIdea = async () => {
      try {
        idea.value = await ideaService.getIdea(project, ideaId);
      } catch (error) {
        console.error('Failed to load idea', error);
      }
    };

    const saveIdea = async () => {
        if (!idea.value) return;
        try {
            await ideaService.updateIdea(project, idea.value);
            // Show toast or alert?
        } catch (e) {
            console.error('Failed to save', e);
        }
    };
    
    const deleteDetail = async () => {
         if (!confirm('Wirklich löschen?')) return;
         try {
             await ideaService.deleteIdea(project, ideaId);
             router.back();
         } catch(e) {
             console.error(e);
         }
    };

    const addMilestone = () => {
        if (!newMilestoneTitle.value || !idea.value) return;
        idea.value.milestones.push({
            id: Math.random().toString(36).substring(7),
            title: newMilestoneTitle.value,
            isCompleted: false
        });
        newMilestoneTitle.value = '';
    };

    const removeMilestone = (index: number) => {
        if (!idea.value) return;
        idea.value.milestones.splice(index, 1);
    };

    const addAsset = () => {
        if (!newAssetName.value || !newAssetUrl.value || !idea.value) return;
        idea.value.assets.push({
            id: Math.random().toString(36).substring(7),
            type: newAssetType.value,
            name: newAssetName.value,
            url: newAssetUrl.value
        });
        newAssetName.value = '';
        newAssetUrl.value = '';
    };

    const removeAsset = (index: number) => {
        if (!idea.value) return;
        idea.value.assets.splice(index, 1);
    };

    onMounted(loadIdea);

    return { 
        idea, segment, save, trash, saveIdea, deleteDetail,
        newMilestoneTitle, addMilestone, removeMilestone,
        newAssetType, newAssetName, newAssetUrl, addAsset, removeAsset
    };
  }
});
</script>
