<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <h1 style="text-align: center; margin-top: 2rem;">Manage APIs</h1>
            <div class="action-buttons">
              <ion-button @click="openAddApiModal">
                <ion-icon slot="start" name="add-circle-outline"></ion-icon>
                Add New API
              </ion-button>
              <ion-button v-if="selectMode" @click="confirmBulkDelete" color="danger">
                <ion-icon slot="start" name="trash-outline"></ion-icon>
                Delete Selected
              </ion-button>
              <ion-button @click="toggleSelectMode">
                <ion-icon slot="start" :name="selectMode ? 'close-circle-outline' : 'checkbox-outline'"></ion-icon>
                {{ selectMode ? 'Cancel Selection' : 'Select Multiple' }}
              </ion-button>
            </div>

            <ion-list v-if="apis.length > 0">
              <ion-item-sliding v-for="api in apis" :key="api.id">
                <ion-item>
                  <ion-checkbox v-if="selectMode" 
                    @ionChange="toggleApiSelection(api.id)" 
                    slot="start">
                  </ion-checkbox>
                  <ion-icon :name="api.icon" slot="start"></ion-icon>
                  <ion-label>
                    <h2>{{ api.name }}</h2>
                    <p>{{ api.description || 'No description' }}</p>
                    <p><strong>Type:</strong> {{ api.type }} | <strong>Auth:</strong> {{ api.auth_type }}</p>
                    <ion-badge color="success" v-if="api.status === 'active'">Active</ion-badge>
                    <ion-badge color="warning" v-else-if="api.status === 'testing'">Testing</ion-badge>
                    <ion-badge color="danger" v-else-if="api.status === 'inactive'">Inactive</ion-badge>
                  </ion-label>
                  <ion-button fill="clear" slot="end" @click="viewApi(api)">
                    <ion-icon slot="icon-only" name="eye-outline"></ion-icon>
                  </ion-button>
                  <ion-button fill="clear" slot="end" @click="openSettings(api)">
                    <ion-icon slot="icon-only" name="settings-outline"></ion-icon>
                  </ion-button>
                </ion-item>
                <ion-item-options>
                  <ion-item-option @click="editApi(api)" color="primary">
                    <ion-icon class="option-icon" name="create-outline"></ion-icon>
                    Edit
                  </ion-item-option>
                  <ion-item-option @click="confirmDelete(api)" color="danger">
                    <ion-icon class="option-icon" name="trash-outline"></ion-icon>
                    Delete
                  </ion-item-option>
                </ion-item-options>
              </ion-item-sliding>
            </ion-list>
            <ion-item v-else>
              <ion-label class="ion-text-center">
                <h2>No APIs found</h2>
                <p>Add a new API to get started</p>
              </ion-label>
            </ion-item>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
      
      <!-- Add/Edit API Modal -->
      <ion-modal :is-open="isModalOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeModal">Cancel</ion-button>
            </ion-buttons>
            <ion-title>{{ isEditing ? 'Edit API' : 'Add New API' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="saveApi">Save</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-item>
            <ion-label position="floating">Name *</ion-label>
            <ion-input v-model="currentApi.name" type="text" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Slug</ion-label>
            <ion-input v-model="currentApi.slug" type="text" :placeholder="slugPreview"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Description</ion-label>
            <ion-textarea v-model="currentApi.description"></ion-textarea>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Icon</ion-label>
            <ion-input v-model="currentApi.icon" type="text"></ion-input>
            <ion-icon
              slot="end"
              size="large"
              :name="currentApi.icon || 'code-outline'"
            ></ion-icon>
          </ion-item>
          <ion-item>
            <ion-label>Type</ion-label>
            <ion-select v-model="currentApi.type" placeholder="Select API Type">
              <ion-select-option value="REST">REST API</ion-select-option>
              <ion-select-option value="GraphQL">GraphQL</ion-select-option>
              <ion-select-option value="WebSocket">WebSocket</ion-select-option>
              <ion-select-option value="SOAP">SOAP</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Base URL</ion-label>
            <ion-input v-model="currentApi.base_url" type="url" placeholder="https://api.example.com"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label>Authentication Type</ion-label>
            <ion-select v-model="currentApi.auth_type" placeholder="Select Auth Type">
              <ion-select-option value="none">No Authentication</ion-select-option>
              <ion-select-option value="api_key">API Key</ion-select-option>
              <ion-select-option value="bearer">Bearer Token</ion-select-option>
              <ion-select-option value="oauth2">OAuth 2.0</ion-select-option>
              <ion-select-option value="basic">Basic Auth</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item v-if="isEditing">
            <ion-label>Status</ion-label>
            <ion-select v-model="currentApi.status" placeholder="Select Status">
              <ion-select-option value="active">Active</ion-select-option>
              <ion-select-option value="inactive">Inactive</ion-select-option>
              <ion-select-option value="testing">Testing</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item v-if="isEditing">
            <ion-label position="floating">Rate Limit (requests/minute)</ion-label>
            <ion-input v-model="currentApi.rate_limit" type="number" min="1"></ion-input>
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  IonPage, IonContent, IonGrid, IonRow, IonCol, IonList, IonItem, 
  IonLabel, IonButton, IonIcon, IonBadge, IonItemSliding, IonItemOptions, 
  IonItemOption, IonCheckbox, IonModal, IonHeader, IonToolbar, IonButtons,
  IonTitle, IonInput, IonTextarea, IonSelect, IonSelectOption,
  alertController, toastController
} from '@ionic/vue';
import axios from 'axios';

interface ApiItem {
  id?: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  type: string;
  base_url: string;
  auth_type: string;
  status: string;
  rate_limit: number;
}

export default defineComponent({
  name: 'ManageApis',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonList, IonItem,
    IonLabel, IonButton, IonIcon, IonBadge, IonItemSliding, IonItemOptions,
    IonItemOption, IonCheckbox, IonModal, IonHeader, IonToolbar, IonButtons,
    IonTitle, IonInput, IonTextarea, IonSelect, IonSelectOption
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const apis = ref<ApiItem[]>([]);
    const selectMode = ref(false);
    const selectedApis = ref<number[]>([]);
    const isModalOpen = ref(false);
    const isEditing = ref(false);
    
    const currentApi = ref<ApiItem>({
      name: '',
      slug: '',
      description: '',
      icon: 'code-outline',
      type: 'REST',
      base_url: '',
      auth_type: 'none',
      status: 'inactive',
      rate_limit: 100
    });

    const slugPreview = computed(() => {
      if (currentApi.value.name) {
        return currentApi.value.name.toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim('-');
      }
      return '';
    });

    const loadApis = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        const response = await axios.post('/backend/apis.php', {
          getApis: true,
          project: route.params.project
        }, {
          headers: {
            'Authorization': token,
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });
        
        if (response.data && !response.data.error) {
          apis.value = response.data;
        }
      } catch (error) {
        console.error('Error loading APIs:', error);
        showToast('Error loading APIs', 'danger');
      }
    };

    const openAddApiModal = () => {
      isEditing.value = false;
      currentApi.value = {
        name: '',
        slug: '',
        description: '',
        icon: 'code-outline',
        type: 'REST',
        base_url: '',
        auth_type: 'none',
        status: 'inactive',
        rate_limit: 100
      };
      isModalOpen.value = true;
    };

    const editApi = (api: ApiItem) => {
      isEditing.value = true;
      currentApi.value = { ...api };
      isModalOpen.value = true;
    };

    const closeModal = () => {
      isModalOpen.value = false;
      isEditing.value = false;
    };

    const saveApi = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        const slug = currentApi.value.slug || slugPreview.value;
        
        const payload = {
          ...(isEditing.value ? { updateApi: true, apiId: currentApi.value.id } : { addApi: true }),
          project: route.params.project,
          name: currentApi.value.name,
          slug: slug,
          description: currentApi.value.description,
          icon: currentApi.value.icon,
          type: currentApi.value.type,
          base_url: currentApi.value.base_url,
          auth_type: currentApi.value.auth_type,
          status: currentApi.value.status,
          rate_limit: currentApi.value.rate_limit
        };

        const response = await axios.post('/backend/apis.php', payload, {
          headers: {
            'Authorization': token,
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });

        if (response.data && response.data.success) {
          showToast(isEditing.value ? 'API updated successfully' : 'API added successfully', 'success');
          closeModal();
          loadApis();
        } else {
          showToast(response.data.error || 'Error saving API', 'danger');
        }
      } catch (error) {
        console.error('Error saving API:', error);
        showToast('Error saving API', 'danger');
      }
    };

    const confirmDelete = async (api: ApiItem) => {
      const alert = await alertController.create({
        header: 'Confirm Delete',
        message: `Are you sure you want to delete "${api.name}"?`,
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Delete',
            role: 'destructive',
            handler: () => deleteApi(api.id!)
          }
        ]
      });
      await alert.present();
    };

    const deleteApi = async (apiId: number) => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        const response = await axios.post('/backend/apis.php', {
          deleteApi: true,
          apiId: apiId
        }, {
          headers: {
            'Authorization': token,
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });

        if (response.data && response.data.success) {
          showToast('API deleted successfully', 'success');
          loadApis();
        } else {
          showToast(response.data.error || 'Error deleting API', 'danger');
        }
      } catch (error) {
        console.error('Error deleting API:', error);
        showToast('Error deleting API', 'danger');
      }
    };

    const viewApi = (api: ApiItem) => {
      router.push(`/project/${route.params.project}/apis/${api.slug}`);
    };

    const openSettings = (api: ApiItem) => {
      router.push(`/project/${route.params.project}/apis/${api.slug}/settings`);
    };

    const toggleSelectMode = () => {
      selectMode.value = !selectMode.value;
      if (!selectMode.value) {
        selectedApis.value = [];
      }
    };

    const toggleApiSelection = (apiId: number) => {
      const index = selectedApis.value.indexOf(apiId);
      if (index > -1) {
        selectedApis.value.splice(index, 1);
      } else {
        selectedApis.value.push(apiId);
      }
    };

    const confirmBulkDelete = async () => {
      if (selectedApis.value.length === 0) return;
      
      const alert = await alertController.create({
        header: 'Confirm Bulk Delete',
        message: `Are you sure you want to delete ${selectedApis.value.length} selected APIs?`,
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Delete All',
            role: 'destructive',
            handler: () => bulkDeleteApis()
          }
        ]
      });
      await alert.present();
    };

    const bulkDeleteApis = async () => {
      try {
        for (const apiId of selectedApis.value) {
          await deleteApi(apiId);
        }
        selectedApis.value = [];
        selectMode.value = false;
      } catch (error) {
        console.error('Error bulk deleting APIs:', error);
        showToast('Error deleting APIs', 'danger');
      }
    };

    const showToast = async (message: string, color: string) => {
      const toast = await toastController.create({
        message,
        duration: 3000,
        color,
        position: 'top'
      });
      await toast.present();
    };

    onMounted(() => {
      loadApis();
    });

    return {
      apis,
      selectMode,
      selectedApis,
      isModalOpen,
      isEditing,
      currentApi,
      slugPreview,
      openAddApiModal,
      editApi,
      closeModal,
      saveApi,
      confirmDelete,
      viewApi,
      openSettings,
      toggleSelectMode,
      toggleApiSelection,
      confirmBulkDelete
    };
  }
});
</script>

<style scoped>
.action-buttons {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.action-buttons ion-button {
  --border-radius: 8px;
}

.option-icon {
  margin-right: 8px;
}

ion-badge {
  margin-left: 8px;
}

h1 {
  color: var(--ion-color-primary);
  font-weight: 600;
}

ion-item h2 {
  color: var(--ion-color-primary);
  font-weight: 500;
}

ion-item p {
  color: var(--ion-color-medium);
  font-size: 0.9em;
}
</style>