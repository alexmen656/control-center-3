<template>
    <ion-page>
        <ion-content class="modern-content">
            <SiteTitle icon="mail-outline" title="Incoming Emails" />
            <div class="page-container">
                <div class="page-header">
                    <div class="header-content">
                        <h1>Incoming Emails</h1>
                    </div>
                    <div class="header-actions">
                        <button class="action-btn secondary" @click="refreshEmails">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="email-layout-wrapper">
                    <ion-grid class="email-grid modern-email-layout">
                        <ion-row class="email-row">
                            <ion-col size-lg="2" size-md="3" size="12" class="folder-col">
                                <div class="data-card folder-card">
                                    <div class="card-header">
                                        <h3>Folders</h3>
                                    </div>
                                    <div class="folder-list">
                                        <div v-for="folder in folders" :key="folder.id" class="folder-item"
                                            :class="{ 'active': currentFolder === folder.id }"
                                            @click="selectFolder(folder.id)">
                                            <div class="folder-icon">
                                                <ion-icon :icon="folder.icon"></ion-icon>
                                            </div>
                                            <span class="folder-name">{{ folder.name }}</span>
                                            <span v-if="folderStats[folder.id]?.unread > 0" class="folder-badge">
                                                {{ folderStats[folder.id].unread }}
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
                            <ion-col size-lg="4" size-md="4" size="12" class="list-col">
                                <div class="data-card list-card">
                                    <div class="list-header">
                                        <div class="search-box">
                                            <ion-icon name="search-outline"></ion-icon>
                                            <input type="text" placeholder="Search..." v-model="searchQuery"
                                                @input="handleSearch">
                                        </div>
                                        <div class="bulk-actions" v-if="selectedEmails.length > 0">
                                            <span class="bulk-count">{{ selectedEmails.length }} selected</span>
                                            <div class="bulk-buttons">
                                                <button class="icon-btn" @click="bulkMarkRead" title="Mark read">
                                                    <ion-icon :icon="mailOpenOutline"></ion-icon>
                                                </button>
                                                <button class="icon-btn danger" @click="bulkDelete" title="Delete">
                                                    <ion-icon :icon="trashOutline"></ion-icon>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="email-list-content">
                                        <div v-if="loading" class="loading-state">
                                            <ion-spinner name="crescent"></ion-spinner>
                                        </div>
                                        <div v-else-if="emails.length === 0" class="empty-state">
                                            <ion-icon :icon="folderOutline"></ion-icon>
                                            <p>No emails in this folder</p>
                                        </div>
                                        <div v-else class="email-items">
                                            <div v-for="email in emails" :key="email.id" class="email-item-row"
                                                :class="{ 'unread': !email.is_read, 'selected': selectedEmail?.id === email.id }"
                                                @click="selectEmail(email)">
                                                <div class="checkbox-wrapper" @click.stop>
                                                    <ion-checkbox :checked="selectedEmails.includes(email.id)"
                                                        @ionChange="toggleEmailSelection(email.id, $event)"></ion-checkbox>
                                                </div>
                                                <div class="email-row-content">
                                                    <div class="row-top">
                                                        <span class="sender-name">{{ email.from_name || email.from_email
                                                            }}</span>
                                                        <span class="date-label">{{ formatDate(email.received_at)
                                                        }}</span>
                                                    </div>
                                                    <div class="subject-line">{{ email.subject || '(No Subject)' }}
                                                    </div>
                                                    <div class="preview-text">{{ email.preview }}</div>
                                                    <div class="badges-row">
                                                        <ion-icon v-if="email.has_attachments" :icon="attachOutline"
                                                            class="attach-icon"></ion-icon>
                                                        <ion-icon v-if="email.is_starred" :icon="star"
                                                            class="star-icon"></ion-icon>
                                                        <span v-if="email.spam_verdict === 'spam'"
                                                            class="spam-tag">SPAM</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pagination-footer" v-if="totalEmails > limit">
                                        <button class="page-btn" :disabled="offset === 0" @click="prevPage">
                                            <ion-icon :icon="chevronBackOutline"></ion-icon>
                                        </button>
                                        <span class="page-count">
                                            {{ offset + 1 }}-{{ Math.min(offset + limit, totalEmails) }} of {{
                                                totalEmails }}
                                        </span>
                                        <button class="page-btn" :disabled="offset + limit >= totalEmails"
                                            @click="nextPage">
                                            <ion-icon :icon="chevronForwardOutline"></ion-icon>
                                        </button>
                                    </div>
                                </div>
                            </ion-col>
                            <ion-col size-lg="6" size-md="5" size="12" class="detail-col">
                                <div class="data-card detail-card" v-if="selectedEmail">
                                    <div class="detail-toolbar">
                                        <div class="toolbar-actions">
                                            <button class="action-btn icon-only"
                                                :class="{ 'active': selectedEmail.is_starred }" @click="toggleStarred"
                                                title="Star">
                                                <ion-icon :icon="selectedEmail.is_starred ? star : starOutline">
                                                </ion-icon>
                                            </button>
                                            <button class="action-btn icon-only" @click="archiveEmail" title="Archive">
                                                <ion-icon :icon="archiveOutline"></ion-icon>
                                            </button>
                                            <button class="action-btn icon-only danger" @click="deleteEmail"
                                                title="Delete">
                                                <ion-icon :icon="trashOutline"></ion-icon>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="detail-content scrollable">
                                        <div class="email-headers">
                                            <h2>{{ selectedEmail.subject || '(No Subject)' }}</h2>
                                            <div class="sender-meta">
                                                <div class="avatar-circle">
                                                    {{ (selectedEmail.from_name ||
                                                        selectedEmail.from_email)[0].toUpperCase() }}
                                                </div>
                                                <div class="meta-texts">
                                                    <div class="from-line">
                                                        <strong>{{ selectedEmail.from_name }}</strong>
                                                        <span class="email-addr">&lt;{{ selectedEmail.from_email
                                                            }}&gt;</span>
                                                    </div>
                                                    <div class="to-line">To: {{ selectedEmail.to_email }}</div>
                                                    <div class="time-line">{{ formatFullDate(selectedEmail.received_at)
                                                        }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="attachments-area" v-if="emailDetail?.attachments?.length > 0">
                                            <h4>
                                                <ion-icon :icon="attachOutline"></ion-icon>
                                                Attachments ({{ emailDetail.attachments.length }})
                                            </h4>
                                            <div class="attachment-chips">
                                                <div v-for="att in emailDetail.attachments" :key="att.id"
                                                    class="att-chip" @click="downloadAttachment(att)">
                                                    <ion-icon :icon="documentOutline"></ion-icon>
                                                    <span>{{ att.filename }}</span>
                                                    <span class="fsize">{{ formatFileSize(att.size) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="body-container">
                                            <div class="view-toggle" v-if="emailDetail?.body_html">
                                                <button class="toggle-btn" :class="{ active: !showRawEmail }"
                                                    @click="showRawEmail = false">HTML</button>
                                                <button class="toggle-btn" :class="{ active: showRawEmail }"
                                                    @click="showRawEmail = true">Raw</button>
                                            </div>
                                            <div v-if="!showRawEmail && sanitizedHtml" class="html-view"
                                                v-html="sanitizedHtml"></div>
                                            <div v-else class="raw-view">
                                                <pre>{{ emailDetail?.body_text || emailDetail?.body_html }}</pre>
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

        const apiBase = 'emails.php'; // Relative to baseURL in axios config

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
                    const email = emails.value.find(e => e.id === id);
                    if (email) email.is_read = true;
                    // Update stats
                    fetchFolderStats();
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
            const baseUrl = axios.defaults.baseURL || import.meta.env.VITE_API_URL || '/backend';
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
            fetchEmails, selectFolder, selectEmail, toggleEmailSelection, refreshEmails,
            handleSearch, prevPage, nextPage, toggleStarred, archiveEmail, deleteEmail,
            bulkMarkRead, bulkDelete, downloadAttachment, formatDate, formatFullDate, formatFileSize,
            mailOutline, mailOpenOutline, trashOutline, starOutline, star,
            archiveOutline, refreshOutline, attachOutline, documentOutline,
            chevronBackOutline, chevronForwardOutline, codeOutline,
            folderOutline, sendOutline, alertCircleOutline
        };
    }
});
</script>

<style scoped>
.modern-content {
    --background: #ffffff;
    --surface: #f8fafc;
    --border: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --primary-color: #2563eb;
    --success-color: #059669;
    --danger-color: #dc2626;
    --warning-color: #d97706;
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --radius: 8px;
    --radius-lg: 12px;
}

@media (prefers-color-scheme: dark) {
    .modern-content {
        --background: #121212;
        --surface: #1a1a1a;
        --border: #2a2a2a;
        --text-primary: #f1f5f9;
        --text-secondary: #b0b0b0;
    }
}

.page-container {
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.page-header {
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.header-content h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: var(--radius);
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn.secondary {
    background: white;
    border: 1px solid var(--border);
    color: var(--text-primary);
}

.action-btn.secondary:hover {
    background: var(--surface);
}

.email-layout-wrapper {
    flex: 1;
    min-height: 0;
}

.modern-email-layout {
    height: 100%;
    padding: 0;
}

.email-row {
    height: 100%;
}

.folder-col,
.list-col,
.detail-col {
    height: 100%;
    padding: 8px;
}

.folder-card,
.list-card,
.detail-card,
.empty-detail-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
}

.card-header,
.list-header,
.detail-toolbar {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

.stats-header {
    border-top: 1px solid var(--border);
}

.folder-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.folder-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: var(--radius);
    cursor: pointer;
    margin-bottom: 4px;
    color: var(--text-secondary);
    transition: all 0.2s;
}

.folder-item:hover {
    background: var(--surface);
    color: var(--text-primary);
}

.folder-item.active {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
    font-weight: 600;
}

.folder-icon {
    margin-right: 12px;
    font-size: 18px;
    display: flex;
}

.folder-name {
    flex: 1;
    font-size: 14px;
}

.folder-badge {
    background: var(--primary-color);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
}

.stats-content {
    padding: 16px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13px;
    color: var(--text-secondary);
}

.list-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: var(--background);
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 0 12px;
    height: 40px;
}

.search-box ion-icon {
    color: var(--text-secondary);
    margin-right: 8px;
}

.search-box input {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    color: var(--text-primary);
    font-size: 14px;
}

.bulk-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(37, 99, 235, 0.1);
    padding: 8px 12px;
    border-radius: 6px;
}

.bulk-count {
    font-size: 13px;
    color: var(--primary-color);
    font-weight: 600;
}

.email-list-content {
    flex: 1;
    overflow-y: auto;
}

.email-item-row {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    transition: background 0.1s;
    background: var(--background);
}

.email-item-row:hover {
    background: var(--surface);
}

.email-item-row.unread {
    background: #fff;
}

.email-item-row.unread .sender-name,
.email-item-row.unread .subject-line {
    font-weight: 700;
    color: var(--text-primary);
}

.email-item-row.selected {
    background: rgba(37, 99, 235, 0.05);
    border-left: 3px solid var(--primary-color);
    padding-left: 13px;
}

.checkbox-wrapper {
    margin-right: 12px;
    padding-top: 2px;
}

.sender-name {
    font-size: 14px;
    color: var(--text-primary);
}

.date-label {
    font-size: 12px;
    color: var(--text-secondary);
}

.subject-line {
    font-size: 14px;
    color: var(--text-primary);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.preview-text {
    font-size: 13px;
    color: var(--text-secondary);
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

.spam-tag {
    background: #fee2e2;
    color: #dc2626;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
}

.pagination-footer {
    padding: 12px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-btn {
    background: transparent;
    border: 1px solid var(--border);
    cursor: pointer;
    padding: 6px;
    border-radius: 4px;
    display: flex;
    color: var(--text-primary);
}

.page-btn:hover:not(:disabled) {
    background: var(--surface);
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: default;
}

.detail-toolbar {
    display: flex;
    justify-content: flex-end;
    background: var(--background);
}

.toolbar-actions {
    display: flex;
    gap: 8px;
}

.action-btn.icon-only {
    width: 36px;
    height: 36px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: var(--text-secondary);
}

.action-btn.icon-only:hover {
    background: var(--surface);
    color: var(--text-primary);
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

.email-headers h2 {
    font-size: 20px;
    margin: 0 0 16px 0;
    color: var(--text-primary);
}

.sender-meta {
    display: flex;
    gap: 16px;
}

.avatar-circle {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}

.from-line {
    font-size: 14px;
    color: var(--text-primary);
}

.email-addr {
    color: var(--text-secondary);
    font-weight: normal;
}

.to-line,
.time-line {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 2px;
}

.attachments-area h4 {
    font-size: 13px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 24px 0 12px 0;
}

.att-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    margin-right: 8px;
    color: var(--text-primary);
}

.body-container {
    margin-top: 24px;
    font-size: 15px;
    line-height: 1.6;
    color: var(--text-primary);
}

.view-toggle {
    display: flex;
    gap: 1px;
    background: var(--border);
    padding: 2px;
    border-radius: 6px;
    display: inline-flex;
    margin-bottom: 16px;
}

.toggle-btn {
    border: none;
    background: transparent;
    padding: 4px 12px;
    font-size: 12px;
    cursor: pointer;
    border-radius: 4px;
}

.toggle-btn.active {
    background: white;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.empty-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-secondary);
    text-align: center;
    padding: 20px;
}

.empty-placeholder ion-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.2;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: var(--text-secondary);
}

@media (max-width: 992px) {
    .folder-col {
        display: none;
    }
}
</style>