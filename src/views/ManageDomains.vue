<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="globe-outline" title="Domain Management" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Domain Management</h1>
            <p>Manage your domains, registrars and expiry dates</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="fetchCloudflare">
              <ion-icon name="cloud-download-outline"></ion-icon>
              Cloudflare Sync
            </button>
            <button class="action-btn primary" @click="openModal()">
              <ion-icon name="add-outline"></ion-icon>
              New Domain
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="globe-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ domains.length }}</h3>
              <p>Total Domains</p>
            </div>
          </div>
          <div class="stat-card warning" v-if="expiringDomains.length > 0">
            <div class="stat-icon">
              <ion-icon name="warning-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ expiringDomains.length }}</h3>
              <p>Expiring Soon</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="cloud-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ cloudflareDomains }}</h3>
              <p>Cloudflare</p>
            </div>
          </div>
        </div>

        <!-- Expiring Domains Alert -->
        <div v-if="expiringDomains.length > 0" class="alert-card warning">
          <ion-icon name="warning-outline" class="alert-icon"></ion-icon>
          <div class="alert-content">
            <h4>⚠️ Domains Expiring Soon</h4>
            <p>{{ expiringDomains.length }} domain(s) will expire in the next 30 days</p>
            <div class="expiring-list">
              <div v-for="domain in expiringDomains" :key="domain.id" class="expiring-item">
                <strong>{{ domain.domain }}</strong> - expires on {{ formatDate(domain.expiry_date) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Domains Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Alle Domains</h3>
              <span class="entry-count">{{ filteredDomains.length }} domain{{ filteredDomains.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Domain suchen..." v-model="searchTerm">
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading domains...</p>
            </div>

            <div v-else-if="filteredDomains.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="globe-outline" class="no-data-icon"></ion-icon>
                <h4>No Domains Found</h4>
                <p>{{ searchTerm ? 'No domains match your search.' : 'No domains available.' }}</p>
              </div>
            </div>

            <div v-else class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="header-cell">Domain</div>
                <div class="header-cell">Registrar</div>
                <div class="header-cell">Purchase Date</div>
                <div class="header-cell">Expiry Date</div>
                <div class="header-cell">Status</div>
                <div class="header-cell actions-header">Actions</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <div v-for="domain in filteredDomains" :key="domain.id" class="table-row">
                  <div class="table-cell cell-domain">
                    <div class="domain-info">
                      <ion-icon name="globe-outline" class="domain-icon"></ion-icon>
                      <span class="domain-name">{{ domain.domain }}</span>
                      <ion-icon v-if="domain.cloudflare_zone_id" name="cloud-outline" class="cf-badge" title="Cloudflare"></ion-icon>
                    </div>
                  </div>

                  <div class="table-cell">
                    <span class="registrar">{{ domain.registrar || '-' }}</span>
                  </div>

                  <div class="table-cell">
                    <span class="date">{{ domain.buy_date ? formatDate(domain.buy_date) : '-' }}</span>
                  </div>

                  <div class="table-cell">
                    <span class="date" :class="getExpiryClass(domain.expiry_date)">
                      {{ domain.expiry_date ? formatDate(domain.expiry_date) : '-' }}
                    </span>
                  </div>

                  <div class="table-cell">
                    <span class="status-badge" :class="getStatusClass(domain.expiry_date)">
                      {{ getStatusText(domain.expiry_date) }}
                    </span>
                  </div>

                  <div class="table-cell cell-actions">
                    <button class="action-icon-btn" @click="openModal(domain)" title="Edit">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                    <button class="action-icon-btn delete" @click="deleteDomain(domain)" title="Delete">
                      <ion-icon name="trash-outline"></ion-icon>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal for Add/Edit Domain -->
      <ion-modal :is-open="showModal" @didDismiss="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ editingDomain ? 'Edit Domain' : 'New Domain' }}</h2>
            <button class="close-btn" @click="closeModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label>Domain *</label>
              <input type="text" v-model="formData.domain" placeholder="example.com" required>
            </div>

            <div class="form-group">
              <label>Registrar</label>
              <input type="text" v-model="formData.registrar" placeholder="e.g. Namecheap, GoDaddy...">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Purchase Date</label>
                <input type="date" v-model="formData.buy_date">
              </div>

              <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" v-model="formData.expiry_date">
              </div>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="formData.auto_renew">
                <span>Auto-renewal enabled</span>
              </label>
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea v-model="formData.notes" rows="3" placeholder="Additional information..."></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn secondary" @click="closeModal">Cancel</button>
            <button class="btn primary" @click="saveDomain">
              {{ editingDomain ? 'Save' : 'Add' }}
            </button>
          </div>
        </div>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { IonPage, IonContent, IonIcon, IonModal } from '@ionic/vue';
import SiteTitle from '../components/SiteTitle.vue';
import axios from 'axios';
import { alertController, toastController } from '@ionic/vue';

interface Domain {
  id: number;
  domain: string;
  registrar: string | null;
  buy_date: string | null;
  expiry_date: string | null;
  cloudflare_zone_id: string | null;
  auto_renew: boolean;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

const domains = ref<Domain[]>([]);
const expiringDomains = ref<Domain[]>([]);
const loading = ref(false);
const searchTerm = ref('');
const showModal = ref(false);
const editingDomain = ref<Domain | null>(null);

const formData = ref({
  domain: '',
  registrar: '',
  buy_date: '',
  expiry_date: '',
  auto_renew: false,
  notes: ''
});

const filteredDomains = computed(() => {
  if (!searchTerm.value) return domains.value;
  const term = searchTerm.value.toLowerCase();
  return domains.value.filter(d => 
    d.domain.toLowerCase().includes(term) ||
    (d.registrar && d.registrar.toLowerCase().includes(term))
  );
});

const cloudflareDomains = computed(() => {
  return domains.value.filter(d => d.cloudflare_zone_id).length;
});

onMounted(() => {
  loadDomains();
  loadExpiringDomains();
});

async function loadDomains() {
  loading.value = true;
  try {
    const response = await axios.get('v2/domains');

    if (response.data.success) {
      domains.value = response.data.domains;
    }
  } catch (error) {
    console.error('Error loading domains:', error);
    showToast('Error loading domains', 'danger');
  } finally {
    loading.value = false;
  }
}

async function loadExpiringDomains() {
  try {
    const response = await axios.get('v2/domains/expiring');

    if (response.data.success) {
      expiringDomains.value = response.data.domains;
    }
  } catch (error) {
    console.error('Error loading expiring domains:', error);
  }
}

async function fetchCloudflare() {
  loading.value = true;
  try {
    const response = await axios.post('v2/domains/fetch-cloudflare');

    if (response.data.success) {
      showToast(response.data.message, 'success');
      await loadDomains();
    } else {
      showToast(response.data.error || 'Error syncing with Cloudflare', 'warning');
    }
  } catch (error) {
    console.error('Error fetching cloudflare domains:', error);
    showToast('Error syncing with Cloudflare', 'danger');
  } finally {
    loading.value = false;
  }
}

function openModal(domain?: Domain) {
  if (domain) {
    editingDomain.value = domain;
    formData.value = {
      domain: domain.domain,
      registrar: domain.registrar || '',
      buy_date: domain.buy_date || '',
      expiry_date: domain.expiry_date || '',
      auto_renew: domain.auto_renew,
      notes: domain.notes || ''
    };
  } else {
    editingDomain.value = null;
    formData.value = {
      domain: '',
      registrar: '',
      buy_date: '',
      expiry_date: '',
      auto_renew: false,
      notes: ''
    };
  }
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingDomain.value = null;
}

async function saveDomain() {
  if (!formData.value.domain) {
    showToast('Please enter a domain', 'warning');
    return;
  }

  try {
    const data: any = {
      ...formData.value
    };

    if (editingDomain.value) {
      data.id = editingDomain.value.id;
    }

    const response = await axios.post('v2/domains', data);

    if (response.data.success) {
      showToast(response.data.message, 'success');
      closeModal();
      await loadDomains();
      await loadExpiringDomains();
    } else {
      showToast(response.data.error || 'Error saving domain', 'danger');
    }
  } catch (error) {
    console.error('Error saving domain:', error);
    showToast('Error saving domain', 'danger');
  }
}

async function deleteDomain(domain: Domain) {
  const alert = await alertController.create({
    header: 'Delete Domain',
    message: `Do you really want to delete the domain "${domain.domain}"?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: async () => {
          try {
            const response = await axios.delete('v2/domains/' + domain.id);

            if (response.data.success) {
              showToast('Domain deleted', 'success');
              await loadDomains();
              await loadExpiringDomains();
            } else {
              showToast(response.data.error || 'Error deleting domain', 'danger');
            }
          } catch (error) {
            console.error('Error deleting domain:', error);
            showToast('Error deleting domain', 'danger');
          }
        }
      }
    ]
  });

  await alert.present();
}

function formatDate(date: string | null): string {
  if (!date) return '-';
  const d = new Date(date);
  return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function getExpiryClass(expiryDate: string | null): string {
  if (!expiryDate) return '';
  const now = new Date();
  const expiry = new Date(expiryDate);
  const daysUntilExpiry = Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

  if (daysUntilExpiry < 0) return 'expired';
  if (daysUntilExpiry <= 7) return 'critical';
  if (daysUntilExpiry <= 30) return 'warning';
  return '';
}

function getStatusClass(expiryDate: string | null): string {
  if (!expiryDate) return 'unknown';
  const now = new Date();
  const expiry = new Date(expiryDate);
  const daysUntilExpiry = Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

  if (daysUntilExpiry < 0) return 'expired';
  if (daysUntilExpiry <= 7) return 'critical';
  if (daysUntilExpiry <= 30) return 'warning';
  return 'active';
}

function getStatusText(expiryDate: string | null): string {
  if (!expiryDate) return 'Unknown';
  const now = new Date();
  const expiry = new Date(expiryDate);
  const daysUntilExpiry = Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

  if (daysUntilExpiry < 0) return 'Expired';
  if (daysUntilExpiry <= 7) return 'Critical';
  if (daysUntilExpiry <= 30) return 'Expiring Soon';
  return 'Active';
}

async function showToast(message: string, color: string = 'primary') {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color,
    position: 'top'
  });
  await toast.present();
}
</script>

<style scoped>
/* Modern Design System - Same as ManageUsers */
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

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover {
  background: var(--background);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.action-btn ion-icon {
  font-size: 16px;
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

.stat-card.warning {
  background: var(--surface);
  border-left: 4px solid var(--warning-color);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  /*color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);*/
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  flex-shrink: 0;
}

.stat-card.warning .stat-icon {
  background: linear-gradient(135deg, var(--warning-color) 0%, #ea580c 100%);
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

/* Alert Card */
.alert-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  margin-bottom: 32px;
  display: flex;
  gap: 16px;
  box-shadow: var(--shadow);
}

.alert-card.warning {
  background: var(--surface);
  border-left: 4px solid var(--warning-color);
}

.alert-icon {
  font-size: 28px;
  color: var(--warning-color);
  flex-shrink: 0;
}

.alert-content h4 {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--text-primary);
}

.alert-content p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
}

.expiring-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.expiring-item {
  padding: 8px 12px;
  background: var(--background);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.expiring-item strong {
  color: var(--warning-color);
  font-weight: 600;
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
  padding: 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
  color: var(--text-primary);
}

.entry-count {
  padding: 4px 12px;
  background: var(--background);
  border-radius: 12px;
  font-size: 13px;
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-box input {
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  min-width: 300px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Table */
.table-wrapper {
  overflow-x: auto;
}

.loading-state,
.no-data-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 8px 0;
}

.no-data-content p {
  color: var(--text-secondary);
  margin: 0;
}

.modern-table {
  width: 100%;
  min-width: 800px;
  background: var(--surface);
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.actions-header {
  flex: 0 0 120px;
  justify-content: center;
}

.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-row:last-child {
  border-bottom: none;
}

.table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.cell-actions {
  flex: 0 0 120px;
  justify-content: center;
  padding: 12px 16px;
  gap: 8px;
}

.cell-domain .domain-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.domain-icon {
  font-size: 20px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.domain-name {
  font-weight: 600;
  color: var(--text-primary);
}

.cf-badge {
  font-size: 16px;
  color: #f38020;
  flex-shrink: 0;
}

.registrar {
  color: var(--text-secondary);
}

.date {
  font-family: 'Monaco', 'Courier New', monospace;
  font-size: 13px;
  color: var(--text-primary);
}

.date.warning {
  color: var(--warning-color);
  font-weight: 600;
}

.date.critical {
  color: var(--danger-color);
  font-weight: 700;
}

.date.expired {
  color: var(--text-muted);
  text-decoration: line-through;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-badge.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(217, 119, 6, 0.2);
}

.status-badge.critical {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.status-badge.expired {
  background: var(--background);
  color: var(--text-muted);
  border: 1px solid var(--border);
}

.status-badge.unknown {
  background: var(--background);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.action-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--radius);
  border: none;
  background: #eff6ff;
  color: var(--primary-color);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 16px;
}

.action-icon-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.action-icon-btn.delete {
  background: #fef2f2;
  color: var(--danger-color);
}

.action-icon-btn.delete:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

/* Modal */
.modal-content {
  background: var(--surface);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
  color: var(--text-primary);
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius);
  border: none;
  background: var(--background);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 24px;
  color: var(--text-secondary);
}

.close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal !important;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.modal-footer {
  padding: 24px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn {
  padding: 10px 24px;
  border-radius: var(--radius);
  border: none;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn.primary {
  background: var(--primary-color);
  color: white;
}

.btn.primary:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.btn.secondary:hover {
  background: var(--background);
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
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .search-box input {
    min-width: auto;
  }

  .modern-table {
    min-width: 600px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}
</style>
