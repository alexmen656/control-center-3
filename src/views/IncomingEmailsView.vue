<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="mail-outline" title="Incoming Emails" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Incoming Emails</h1>
            <p>View and manage received emails</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshEmails">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>
        </div>

        <!-- Main Content (Split View) -->
        <ion-grid class="email-grid modern-email-layout">
          <ion-row class="email-row">
            <!-- Sidebar: Folders & Stats -->
            <ion-col size-lg="2" size-md="3" size="12" class="folder-col">
              <div class="data-card folder-card">
                <div class="card-header">
                  <h3>Folders</h3>
                </div>
                <div class="folder-list">
                  <div 
                    v-for="folder in folders" 
                    :key="folder.id"
                    class="folder-item"
                    :class="{ 'active': currentFolder === folder.id }"
                    @click="selectFolder(folder.id)"
                  >
                    <div class="folder-icon">
                      <ion-icon :icon="folder.icon"></ion-icon>
                    </div>
                    <span class="folder-name">{{ folder.name }}</span>
                    <span v-if="folderStats[folder.id]?.unread > 0" class="folder-badge">
                      {{ folderStats[folder.id]?.unread }}
                    </span>
                  </div>
                </div>

                <div class="card-header stats-header">
                  <h3>Statistics</h3>
                </div>
                <div class="stats-content">
                  <div class="stat-row">
                    <span>Total</span>
                    <strong>{{ totalEmails }}</strong>
                  </div>
                  <div class="stat-row">
                    <span>Unread</span>
                    <strong>{{ totalUnread }}</strong>
                  </div>
                </div>
              </div>
            </ion-col>

            <!-- Middle: Email List -->
            <ion-col size-lg="4" size-md="4" size="12" class="list-col">
              <div class="data-card list-card">
                <!-- Search & Bulk Actions -->
                <div class="list-header">
                  <div class="search-box">
                    <ion-icon name="search-outline"></ion-icon>
                    <input 
                      type="text" 
                      placeholder="Search..." 
                      v-model="searchQuery"
                      @input="handleSearch"
                    >
                  </div>
                  
                  <div class="bulk-actions" v-if="selectedEmails.length > 0">
                    <span class="bulk-count">{{ selectedEmails.length }} selected</span>
                    <div class="bulk-buttons">
                      <button class="icon-btn" @click="bulkMarkRead" title="Mark Read">
                        <ion-icon :icon="mailOpenOutline"></ion-icon>
                      </button>
                      <button class="icon-btn danger" @click="bulkDelete" title="Delete">
                        <ion-icon :icon="trashOutline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- List Content -->
                <div class="email-list-content">
                  <div v-if="loading" class="loading-state">
                    <ion-spinner name="crescent"></ion-spinner>
                  </div>
                  
                  <div v-else-if="emails.length === 0" class="empty-state">
                    <ion-icon :icon="folderOutline"></ion-icon>
                    <p>No emails in this folder</p>
                  </div>

                  <div v-else class="email-items">
                    <div 
                      v-for="email in emails" 
                      :key="email.id"
                      class="email-item-row"
                      :class="{ 
                        'unread': !email.is_read,
                        'selected': selectedEmail?.id === email.id
                      }"
                      @click="selectEmail(email)"
                    >
                      <div class="checkbox-wrapper" @click.stop>
                        <ion-checkbox 
                          :checked="selectedEmails.includes(email.id)"
                          @ionChange="toggleEmailSelection(email.id, $event)"
                        ></ion-checkbox>
                      </div>
                      
                      <div class="email-row-content">
                        <div class="row-top">
                          <span class="sender-name">{{ email.from_name || email.from_email }}</span>
                          <span class="date-label">{{ formatDate(email.received_at) }}</span>
                        </div>
                        <div class="subject-line">{{ email.subject || '(No Subject)' }}</div>
                        <div class="preview-text">{{ email.preview }}</div>
                        <div class="badges-row">
                          <ion-icon v-if="email.has_attachments" :icon="attachOutline" class="attach-icon"></ion-icon>
                          <ion-icon v-if="email.is_starred" :icon="star" class="star-icon"></ion-icon>
                          <span v-if="email.spam_verdict === 'FAIL'" class="spam-tag">Spam</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-footer" v-if="totalEmails > limit">
                  <button class="page-btn" :disabled="offset === 0" @click="prevPage">
                    <ion-icon :icon="chevronBackOutline"></ion-icon>
                  </button>
                  <span class="page-count">
                    {{ offset + 1 }} - {{ Math.min(offset + limit, totalEmails) }} / {{ totalEmails }}
                  </span>
                  <button class="page-btn" :disabled="offset + limit >= totalEmails" @click="nextPage">
                    <ion-icon :icon="chevronForwardOutline"></ion-icon>
                  </button>
                </div>
              </div>
            </ion-col>

            <!-- Right: Email Detail -->
            <ion-col size-lg="6" size-md="5" size="12" class="detail-col">
              <div class="data-card detail-card" v-if="selectedEmail">
                <div class="detail-toolbar">
                  <div class="toolbar-actions">
                    <button class="action-btn icon-only" @click="toggleStarred" :class="{'active': selectedEmail.is_starred}">
                      <ion-icon :icon="selectedEmail.is_starred ? star : starOutline"></ion-icon>
                    </button>
                    <button class="action-btn icon-only" @click="archiveEmail">
                      <ion-icon :icon="archiveOutline"></ion-icon>
                    </button>
                    <button class="action-btn icon-only danger" @click="deleteEmail">
                      <ion-icon :icon="trashOutline"></ion-icon>
                    </button>
                    <button class="action-btn icon-only" @click="showRawEmail = !showRawEmail" title="Toggle Raw">
                      <ion-icon :icon="codeOutline"></ion-icon>
                    </button>
                  </div>
                </div>

                <div class="detail-content scrollable">
                  <div class="email-headers">
                    <h2>{{ emailDetail?.subject || '(No Subject)' }}</h2>
                    <div class="sender-meta">
                      <div class="avatar-circle">
                         {{ (emailDetail?.from_name || emailDetail?.from_email || '?')[0].toUpperCase() }}
                      </div>
                      <div class="meta-texts">
                        <div class="from-line">
                          <strong>{{ emailDetail?.from_name || 'Unknown' }}</strong>
                          <span class="email-addr">&lt;{{ emailDetail?.from_email }}&gt;</span>
                        </div>
                        <div class="to-line">
                          To: {{ emailDetail?.to_email }}
                          <span v-if="emailDetail?.cc">CC: {{ emailDetail?.cc }}</span>
                        </div>
                        <div class="time-line">{{ formatFullDate(emailDetail?.received_at) }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="attachments-area" v-if="emailDetail?.attachments?.length > 0">
                    <h4><ion-icon :icon="attachOutline"></ion-icon> Attachments ({{ emailDetail.attachments.length }})</h4>
                    <div class="attachment-chips">
                      <div 
                        v-for="att in emailDetail.attachments" 
                        :key="att.id"
                        class="att-chip"
                        @click="downloadAttachment(att)"
                      >
                        <ion-icon :icon="documentOutline"></ion-icon>
                        <span class="fname">{{ att.filename }}</span>
                        <span class="fsize">{{ formatFileSize(att.size) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="body-container">
                    <div v-if="!showRawEmail">
                      <div v-if="emailDetail?.body_html" v-html="sanitizedHtml" class="html-view"></div>
                      <pre v-else class="text-view">{{ emailDetail?.body_text }}</pre>
                    </div>
                    <div v-else class="raw-view">
                      <h4>Headers</h4>
                      <pre>{{ JSON.stringify(emailDetail?.headers, null, 2) }}</pre>
                      <h4>Content</h4>
                      <pre>{{ emailDetail?.raw_email }}</pre>
                    </div>
                  </div>
                </div>
              </div>

              <div class="data-card empty-detail-card" v-else>
                <div class="empty-placeholder">
                  <ion-icon :icon="mailOutline"></ion-icon>
                  <h3>Select an email</h3>
                  <p>Choose an email from the list to read it.</p>
                </div>
              </div>
            </ion-col>
          </ion-row>
        </ion-grid>
      </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, getCurrentInstance } from 'vue';
import {
  IonPage, IonContent, IonGrid, IonRow, IonCol, IonIcon, IonSpinner, IonCheckbox
} from '@ionic/vue';
import {
  mailOutline, mailOpenOutline, trashOutline, starOutline, star,
  archiveOutline, refreshOutline, attachOutline, documentOutline,
  chevronBackOutline, chevronForwardOutline, codeOutline,
  folderOutline, sendOutline, alertCircleOutline
} from 'ionicons/icons';
import DOMPurify from 'dompurify';
import SiteTitle from "@/components/SiteTitle.vue";

interface Email {
  id: number;
  message_id: string;
  from_email: string;
  from_name: string;
  to_email: string;
  subject: string;
  preview: string;
  is_read: boolean;
  is_starred: boolean;
  has_attachments: boolean;
  spam_verdict: string;
  received_at: string;
}

interface EmailDetail extends Email {
  cc: string;
  body_text: string;
  body_html: string;
  headers: Record<string, string>;
  raw_email: string;
  attachments: Attachment[];
}

interface Attachment {
  id: number;
  filename: string;
  content_type: string;
  size: number;
}

interface FolderStats {
  total: number;
  unread: number;
}

export default defineComponent({
  name: 'IncomingEmailsView',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonIcon, IonSpinner, IonCheckbox, SiteTitle
  },
  setup() {
    const { appContext } = getCurrentInstance() as any;
    const axios = appContext.config.globalProperties.$axios;

    const apiBase = 'emails.php'; // Relative to baseURL configured in axios

    const emails = ref<Email[]>([]);
    const selectedEmail = ref<Email | null>(null);
    const emailDetail = ref<EmailDetail | null>(null);
    const selectedEmails = ref<number[]>([]);
    const currentFolder = ref('inbox');
    const searchQuery = ref('');
    const loading = ref(false);
    const showRawEmail = ref(false);
    
    const offset = ref(0);
    const limit = ref(50);
    const totalEmails = ref(0);
    
    const folderStats = ref<Record<string, FolderStats>>({});

    const folders = [
      { id: 'inbox', name: 'Inbox', icon: mailOutline },
      { id: 'starred', name: 'Starred', icon: starOutline },
      { id: 'sent', name: 'Sent', icon: sendOutline },
      { id: 'archive', name: 'Archive', icon: archiveOutline },
      { id: 'spam', name: 'Spam', icon: alertCircleOutline },
      { id: 'trash', name: 'Trash', icon: trashOutline },
    ];

    const totalUnread = computed(() => {
      // Handle potential undefined folderStats by defaulting to empty array
      return Object.values(folderStats.value || {}).reduce((sum: any, stat: any) => sum + (stat?.unread || 0), 0);
    });

    const sanitizedHtml = computed(() => {
      if (!emailDetail.value?.body_html) return '';
      return DOMPurify.sanitize(emailDetail.value.body_html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'a', 'img', 'div', 'span', 
                       'table', 'tr', 'td', 'th', 'thead', 'tbody', 'ul', 'ol', 'li',
                       'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code'],
        ALLOWED_ATTR: ['href', 'src', 'alt', 'style', 'class', 'target'],
      });
    });

    // --- Actions ---

    const fetchEmails = async () => {
      loading.value = true;
      try {
        const response = await axios.get(apiBase, {
            params: {
                action: 'list',
                folder: currentFolder.value,
                limit: limit.value,
                offset: offset.value,
                search: searchQuery.value || undefined
            }
        });

        if (response.data.success) {
          emails.value = response.data.data.emails;
          totalEmails.value = response.data.data.total;
        }
      } catch (error) {
        console.error('Error fetching emails:', error);
      } finally {
        loading.value = false;
      }
    };

    const fetchEmailDetail = async (id: number) => {
      try {
        const response = await axios.get(apiBase, {
            params: {
                action: 'get',
                id: id,
                mark_read: true
            }
        });

        if (response.data.success) {
          emailDetail.value = response.data.data;
          // Local update
          const email = emails.value.find(e => e.id === id);
          if (email) email.is_read = true;
          fetchFolderStats(); // Update stats as unread count changes
        }
      } catch (error) {
        console.error('Error fetching email detail:', error);
      }
    };

    const fetchFolderStats = async () => {
      try {
        const response = await axios.get(apiBase, {
            params: { action: 'stats' }
        });
        if (response.data.success) {
          folderStats.value = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    };

    const selectFolder = (folderId: string) => {
      currentFolder.value = folderId;
      offset.value = 0;
      selectedEmail.value = null;
      emailDetail.value = null;
      fetchEmails();
    };

    const selectEmail = (email: Email) => {
      selectedEmail.value = email;
      showRawEmail.value = false;
      fetchEmailDetail(email.id);
    };

    const toggleEmailSelection = (id: number, event: CustomEvent) => {
      if (event.detail.checked) {
        if (!selectedEmails.value.includes(id)) selectedEmails.value.push(id);
      } else {
        selectedEmails.value = selectedEmails.value.filter(eid => eid !== id);
      }
    };

    const refreshEmails = () => {
      fetchEmails();
      fetchFolderStats();
    };

    let searchTimeout: any;
    const handleSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        offset.value = 0;
        fetchEmails();
      }, 300);
    };

    const prevPage = () => {
      if (offset.value > 0) {
        offset.value = Math.max(0, offset.value - limit.value);
        fetchEmails();
      }
    };

    const nextPage = () => {
      if (offset.value + limit.value < totalEmails.value) {
        offset.value += limit.value;
        fetchEmails();
      }
    };

    const toggleStarred = async () => {
      if (!selectedEmail.value) return;
      const newValue = !selectedEmail.value.is_starred;
      try {
        await axios.post(`${apiBase}?action=mark_starred`, {
            id: selectedEmail.value.id,
            starred: newValue
        });
        
        selectedEmail.value.is_starred = newValue;
        if (emailDetail.value) emailDetail.value.is_starred = newValue;
      } catch (error) {
        console.error('Error starring email', error);
      }
    };

    const archiveEmail = async () => {
      if (!selectedEmail.value) return;
      try {
        await axios.post(`${apiBase}?action=move`, {
            id: selectedEmail.value.id, 
            folder: 'archive'
        });
        emails.value = emails.value.filter(e => e.id !== selectedEmail.value?.id);
        selectedEmail.value = null;
        emailDetail.value = null;
        fetchFolderStats();
      } catch (error) {
        console.error('Error archiving', error);
      }
    };

    const deleteEmail = async () => {
      if (!selectedEmail.value) return;
      try {
        await axios.post(`${apiBase}?action=delete`, {
            id: selectedEmail.value.id
        });
        emails.value = emails.value.filter(e => e.id !== selectedEmail.value?.id);
        selectedEmail.value = null;
        emailDetail.value = null;
        fetchFolderStats();
      } catch (error) {
        console.error('Error deleting', error);
      }
    };

    const bulkMarkRead = async () => {
      if (selectedEmails.value.length === 0) return;
      try {
        await axios.post(`${apiBase}?action=bulk_action`, {
            ids: selectedEmails.value,
            action: 'mark_read'
        });
        emails.value.forEach(e => {
          if (selectedEmails.value.includes(e.id)) e.is_read = true;
        });
        selectedEmails.value = [];
        fetchFolderStats();
      } catch (error) {
        console.error('Error bulk reading', error);
      }
    };

    const bulkDelete = async () => {
      if (selectedEmails.value.length === 0) return;
      try {
        await axios.post(`${apiBase}?action=bulk_action`, {
            ids: selectedEmails.value,
            action: 'delete'
        });
        emails.value = emails.value.filter(e => !selectedEmails.value.includes(e.id));
        selectedEmails.value = [];
        fetchFolderStats();
      } catch (error) {
        console.error('Error bulk delete', error);
      }
    };

    const downloadAttachment = (attachment: Attachment) => {
      // Direct download link logic - axios not needed for opening new tab
      // But we need the full URL. Assuming `apiBase` is relative, this might need adjustment if using baseURL.
      // Easiest is to construct it manually if possible, or use the global axios config.
      const baseUrl = axios.defaults.baseURL || import.meta.env.VITE_API_URL || '/backend';
      // Clean up slashes
      const cleanBase = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
      const url = `${cleanBase}/emails.php?action=get_attachment&id=${attachment.id}&download=true`;
      window.open(url, '_blank');
    };

    const formatDate = (dateString: string) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      const now = new Date();
      const diffDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));
      
      if (diffDays === 0) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      } else if (diffDays < 7) {
        return date.toLocaleDateString([], { weekday: 'short' });
      } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
      }
    };

    const formatFullDate = (dateString: string) => {
      if (!dateString) return '';
      return new Date(dateString).toLocaleString();
    };

    const formatFileSize = (bytes: number) => {
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    onMounted(() => {
      fetchEmails();
      fetchFolderStats();
    });

    return {
      emails, selectedEmail, emailDetail, selectedEmails, currentFolder,
      searchQuery, loading, showRawEmail, offset, limit, totalEmails,
      folderStats, folders, totalUnread, sanitizedHtml,
      // Methods
      fetchEmails, selectFolder, selectEmail, toggleEmailSelection, refreshEmails,
      handleSearch, prevPage, nextPage, toggleStarred, archiveEmail, deleteEmail,
      bulkMarkRead, bulkDelete, downloadAttachment, formatDate, formatFullDate, formatFileSize,
      // Icons
      mailOutline, mailOpenOutline, trashOutline, starOutline, star,
      archiveOutline, refreshOutline, attachOutline, documentOutline,
      chevronBackOutline, chevronForwardOutline, codeOutline,
      folderOutline, sendOutline, alertCircleOutline
    };
  }
});
</script>

<style scoped>
/* Page Layout */
.modern-email-layout {
  height: calc(100vh - 120px);
  padding: 0;
}

.email-row {
  height: 100%;
}

.folder-col, .list-col, .detail-col {
  height: 100%;
  padding: 8px;
}

/* Cards */
.folder-card, .list-card, .detail-card, .empty-detail-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* Soft shadow */
}

/* Folder Sidebar */
.folder-list {
  padding: 12px;
  flex: 1;
  overflow-y: auto;
}

.folder-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
  color: #555;
  margin-bottom: 4px;
}

.folder-item:hover {
  background: #f5f7fa;
}

.folder-item.active {
  background: #ebf5ff; /* Primary light */
  color: var(--ion-color-primary, #0066cc);
  font-weight: 600;
}

.folder-icon {
  margin-right: 12px;
  font-size: 1.2rem;
  display: flex;
}

.folder-name {
  flex: 1;
}

.folder-badge {
  background: var(--ion-color-primary, #0066cc);
  color: white;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: bold;
}

.stats-header {
  border-top: 1px solid #eee;
}

.stats-content {
  padding: 16px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 0.9rem;
  color: #666;
}

/* List Column */
.list-header {
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.search-box {
  display: flex;
  align-items: center;
  background: #f5f7fa;
  border-radius: 8px;
  padding: 0 12px;
  height: 40px;
}

.search-box input {
  border: none;
  background: transparent;
  width: 100%;
  padding: 8px;
  outline: none;
}

.bulk-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ebf5ff;
  padding: 8px 12px;
  border-radius: 6px;
}

.bulk-count {
  font-size: 0.85rem;
  color: var(--ion-color-primary);
  font-weight: 600;
}

.bulk-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #555;
  transition: all 0.2s;
}

.icon-btn:hover {
  background: #f0f0f0;
}

.icon-btn.danger {
  color: var(--ion-color-danger);
  border-color: #ffcccc;
}

.icon-btn.danger:hover {
  background: #fff0f0;
}

.email-list-content {
  flex: 1;
  overflow-y: auto;
  position: relative;
}

.email-item-row {
  padding: 12px 16px 12px 12px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  display: flex;
  align-items: flex-start;
  transition: background 0.1s;
}

.email-item-row:hover {
  background: #fafbfc;
}

.email-item-row.unread {
  background: #ffffff;
}

.email-item-row.unread .sender-name,
.email-item-row.unread .subject-line {
  font-weight: 700;
  color: #111;
}

.email-item-row.selected {
  background: #ebf5ff;
  border-left: 3px solid var(--ion-color-primary);
  padding-left: 9px;
}

.checkbox-wrapper {
  margin-right: 12px;
  padding-top: 2px;
}

.email-row-content {
  flex: 1;
  min-width: 0;
}

.row-top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.sender-name {
  font-size: 0.9rem;
  color: #333;
}

.date-label {
  font-size: 0.75rem;
  color: #888;
}

.subject-line {
  font-size: 0.9rem;
  color: #333;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.preview-text {
  font-size: 0.8rem;
  color: #777;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 6px;
}

.badges-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.attach-icon {
  font-size: 0.9rem;
  color: #666;
}

.star-icon {
  font-size: 0.9rem;
  color: #ffc107;
}

.spam-tag {
  background: #fee2e2;
  color: #dc2626;
  font-size: 0.65rem;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: bold;
}

.pagination-footer {
  padding: 8px 16px;
  border-top: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 4px;
}

.page-btn:hover:not(:disabled) {
  background: #f0f0f0;
}

.page-btn:disabled {
  opacity: 0.3;
  cursor: default;
}

.page-count {
  font-size: 0.85rem;
  color: #666;
}

/* Detail Column */
.detail-toolbar {
  padding: 12px 20px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: flex-end;
}

.toolbar-actions {
  display: flex;
  gap: 12px;
}

.action-btn.icon-only {
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  background: transparent;
  font-size: 1.2rem;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn.icon-only:hover {
  background: #f0f0f0;
  color: #333;
}

.action-btn.icon-only.active {
  color: #ffc107;
}

.action-btn.icon-only.danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

.detail-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.email-headers {
  margin-bottom: 24px;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 20px;
}

.email-headers h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.sender-meta {
  display: flex;
  gap: 16px;
}

.avatar-circle {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  font-weight: bold;
}

.meta-texts {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.from-line {
  font-size: 1rem;
  color: #333;
}

.email-addr {
  color: #666;
  font-weight: normal;
  font-size: 0.9rem;
}

.to-line, .time-line {
  font-size: 0.85rem;
  color: #888;
  margin-top: 2px;
}

.attachments-area {
  margin-bottom: 24px;
}

.attachments-area h4 {
  font-size: 0.9rem;
  color: #555;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.attachment-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.att-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #f5f7fa;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.att-chip:hover {
  background: #eef2f6;
  border-color: #cbd5e1;
}

.fsize {
  color: #888;
  font-size: 0.75rem;
}

.body-container {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #333;
}

.html-view :deep(a) {
  color: var(--ion-color-primary, #0066cc);
  text-decoration: underline;
}

.raw-view {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
  overflow-x: auto;
}

.empty-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #999;
}

.empty-placeholder ion-icon {
  font-size: 4rem;
  margin-bottom: 16px;
  color: #e0e0e0;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #888;
}

.empty-state ion-icon {
  font-size: 3rem;
  margin-bottom: 8px;
  color: #ddd;
}

@media (max-width: 992px) {
  .folder-col { display: none; } /* On smaller screens, maybe move folders to a menu or drawer */
}
</style>