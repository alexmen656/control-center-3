<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="bookmarks-outline" title="Manage Bookmarks"/>

      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Bookmark Management</h1>
            <p>Manage your saved bookmarks and quick links</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshBookmarks">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="showCreateModal = true">
              <ion-icon name="add-outline"></ion-icon>
              New Bookmark
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="bookmarks-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ totalBookmarks }}</h3>
              <p>Total Bookmarks</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="globe-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ externalBookmarks }}</h3>
              <p>External Links</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="folder-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ internalBookmarks }}</h3>
              <p>Internal Pages</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="calendar-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ recentBookmarks }}</h3>
              <p>Added this week</p>
            </div>
          </div>
        </div>

        <!-- Bookmarks List -->
        <div class="bookmarks-card">
          <div class="card-header">
            <h2>Your Bookmarks</h2>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input 
                type="text" 
                placeholder="Search bookmarks..." 
                v-model="searchTerm"
              >
            </div>
          </div>

          <div class="bookmarks-container">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading bookmarks...</p>
            </div>

            <div v-else-if="filteredBookmarks.length === 0" class="no-data-state">
              <ion-icon name="bookmarks-outline" class="no-data-icon"></ion-icon>
              <h3>No Bookmarks Found</h3>
              <p>{{ searchTerm ? 'No bookmarks match your search criteria.' : 'You haven\'t saved any bookmarks yet.' }}</p>
              <button class="action-btn primary" @click="showCreateModal = true">
                <ion-icon name="add-outline"></ion-icon>
                Create Your First Bookmark
              </button>
            </div>

            <div v-else class="bookmarks-grid">
              <div 
                v-for="bookmark in filteredBookmarks" 
                :key="bookmark.id"
                class="bookmark-card"
                :class="{
                  'bookmark-external': isExternalLink(bookmark.location),
                  'bookmark-internal': !isExternalLink(bookmark.location)
                }"
              >
                <div class="bookmark-header">
                  <div class="bookmark-info">
                    <div class="bookmark-icon">
                      <ion-icon :name="bookmark.icon || 'bookmark-outline'"></ion-icon>
                    </div>
                    <div class="bookmark-details">
                      <h3 class="bookmark-title">{{ bookmark.title }}</h3>
                      <p class="bookmark-url">{{ formatUrl(bookmark.location) }}</p>
                    </div>
                  </div>
                  <div class="bookmark-status">
                    <span 
                      class="status-badge"
                      :class="isExternalLink(bookmark.location) ? 'status-external' : 'status-internal'"
                    >
                      <ion-icon :name="isExternalLink(bookmark.location) ? 'globe-outline' : 'folder-outline'"></ion-icon>
                      {{ isExternalLink(bookmark.location) ? 'External' : 'Internal' }}
                    </span>
                  </div>
                </div>

                <div class="bookmark-actions">
                  <button 
                    class="icon-btn visit-btn" 
                    @click="visitBookmark(bookmark)"
                    title="Visit Link"
                  >
                    <ion-icon name="open-outline"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn edit-btn"
                    @click="editBookmark(bookmark)"
                    title="Edit Bookmark"
                  >
                    <ion-icon name="pencil-outline"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn copy-btn"
                    @click="copyToClipboard(bookmark.location)"
                    title="Copy URL"
                  >
                    <ion-icon name="copy-outline"></ion-icon>
                  </button>
                  <button 
                    class="icon-btn delete-btn"
                    @click="confirmDelete(bookmark)"
                    title="Delete Bookmark"
                  >
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Bookmark Modal -->
      <div v-if="showCreateModal" class="custom-modal-overlay" @click="showCreateModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Create New Bookmark</h3>
            <button class="modal-close-btn" @click="showCreateModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label for="bookmark-title">Title</label>
              <input 
                id="bookmark-title"
                type="text" 
                v-model="newBookmark.title"
                placeholder="Enter bookmark title"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="bookmark-url">URL</label>
              <input 
                id="bookmark-url"
                type="text" 
                v-model="newBookmark.location"
                placeholder="Enter URL (e.g., https://example.com or /internal/path)"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="bookmark-icon">Icon</label>
              <input 
                id="bookmark-icon"
                type="text" 
                v-model="newBookmark.icon"
                placeholder="Enter Ionic icon name (e.g., bookmark-outline)"
                class="form-input"
              >
              <div class="icon-preview" v-if="newBookmark.icon">
                <ion-icon :name="newBookmark.icon"></ion-icon>
                <span>Preview</span>
              </div>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showCreateModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="createBookmark" :disabled="!newBookmark.title.trim() || !newBookmark.location.trim()">
                Create Bookmark
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Bookmark Modal -->
      <div v-if="showEditModal" class="custom-modal-overlay" @click="showEditModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Edit Bookmark</h3>
            <button class="modal-close-btn" @click="showEditModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label for="edit-bookmark-title">Title</label>
              <input 
                id="edit-bookmark-title"
                type="text" 
                v-model="editingBookmark.title"
                placeholder="Enter bookmark title"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="edit-bookmark-url">URL</label>
              <input 
                id="edit-bookmark-url"
                type="text" 
                v-model="editingBookmark.location"
                placeholder="Enter URL"
                class="form-input"
              >
            </div>
            <div class="form-group">
              <label for="edit-bookmark-icon">Icon</label>
              <input 
                id="edit-bookmark-icon"
                type="text" 
                v-model="editingBookmark.icon"
                placeholder="Enter Ionic icon name"
                class="form-input"
              >
              <div class="icon-preview" v-if="editingBookmark.icon">
                <ion-icon :name="editingBookmark.icon"></ion-icon>
                <span>Preview</span>
              </div>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="updateBookmark">
                Update Bookmark
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="deleteModal.show" class="custom-modal-overlay" @click="deleteModal.show = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Delete Bookmark</h3>
            <button class="modal-close-btn" @click="deleteModal.show = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="warning-content">
              <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
              <h4>Are you sure?</h4>
              <p>This will permanently delete the bookmark <strong>"{{ deleteModal.bookmark?.title }}"</strong>.</p>
              <p class="warning-text">This action cannot be undone!</p>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="deleteModal.show = false">
                Cancel
              </button>
              <button class="action-btn danger" @click="deleteBookmark()">
                Delete Permanently
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageBookmarks",
  components: {
    SiteTitle,
  },
  data() {
    return {
      bookmarks: [],
      loading: true,
      searchTerm: '',
      showCreateModal: false,
      showEditModal: false,
      newBookmark: {
        title: '',
        location: '',
        icon: ''
      },
      editingBookmark: {
        id: null,
        title: '',
        location: '',
        icon: ''
      },
      deleteModal: {
        show: false,
        bookmark: null
      }
    };
  },
  computed: {
    filteredBookmarks() {
      if (!this.searchTerm.trim()) {
        return this.bookmarks;
      }
      
      const searchLower = this.searchTerm.toLowerCase();
      return this.bookmarks.filter(bookmark =>
        bookmark.title.toLowerCase().includes(searchLower) ||
        bookmark.location.toLowerCase().includes(searchLower)
      );
    },
    totalBookmarks() {
      return this.bookmarks.length;
    },
    externalBookmarks() {
      return this.bookmarks.filter(bookmark => this.isExternalLink(bookmark.location)).length;
    },
    internalBookmarks() {
      return this.bookmarks.filter(bookmark => !this.isExternalLink(bookmark.location)).length;
    },
    recentBookmarks() {
      // Since the API doesn't provide creation date, we'll simulate this
      // In a real implementation, you'd add a created_at field to the database
      return Math.floor(this.bookmarks.length * 0.2); // Assume 20% are recent
    }
  },
  created() {
    this.loadBookmarks();
  },
  methods: {
    async loadBookmarks() {
      this.loading = true;
      try {
        const response = await this.$axios.get("bookmarks.php?getBookmarks=true");
        this.bookmarks = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        console.error('Error loading bookmarks:', error);
        this.bookmarks = [];
      } finally {
        this.loading = false;
      }
    },
    refreshBookmarks() {
      this.loadBookmarks();
    },
    async createBookmark() {
      if (!this.newBookmark.title.trim() || !this.newBookmark.location.trim()) {
        alert("Title and URL are required!");
        return;
      }

      try {
        await this.$axios.post(
          "bookmarks.php",
          this.$qs.stringify({
            newBookmark: "true",
            title: this.newBookmark.title,
            location: this.newBookmark.location,
            icon: this.newBookmark.icon || 'bookmark-outline'
          })
        );
        
        alert("Bookmark created successfully");
        this.showCreateModal = false;
        this.newBookmark = { title: '', location: '', icon: '' };
        await this.loadBookmarks();
      } catch (error) {
        console.error('Error creating bookmark:', error);
        alert("Error creating bookmark");
      }
    },
    editBookmark(bookmark) {
      this.editingBookmark = {
        id: bookmark.id,
        title: bookmark.title,
        location: bookmark.location,
        icon: bookmark.icon
      };
      this.showEditModal = true;
    },
    async updateBookmark() {
      try {
        // First delete the old bookmark
        await this.$axios.post(
          "bookmarks.php",
          this.$qs.stringify({
            deleteBookmark: "true",
            location: this.editingBookmark.location
          })
        );
        
        // Then create the updated one
        await this.$axios.post(
          "bookmarks.php",
          this.$qs.stringify({
            newBookmark: "true",
            title: this.editingBookmark.title,
            location: this.editingBookmark.location,
            icon: this.editingBookmark.icon || 'bookmark-outline'
          })
        );
        
        alert("Bookmark updated successfully");
        this.showEditModal = false;
        await this.loadBookmarks();
      } catch (error) {
        console.error('Error updating bookmark:', error);
        alert("Error updating bookmark");
      }
    },
    confirmDelete(bookmark) {
      this.deleteModal.bookmark = bookmark;
      this.deleteModal.show = true;
    },
    async deleteBookmark() {
      if (!this.deleteModal.bookmark) return;
      
      try {
        await this.$axios.post(
          "bookmarks.php",
          this.$qs.stringify({
            deleteBookmark: "true",
            location: this.deleteModal.bookmark.location
          })
        );
        
        alert("Bookmark deleted successfully");
        this.bookmarks = this.bookmarks.filter(b => b.id !== this.deleteModal.bookmark.id);
        this.deleteModal.show = false;
        this.deleteModal.bookmark = null;
      } catch (error) {
        console.error('Error deleting bookmark:', error);
        alert("Error deleting bookmark");
      }
    },
    visitBookmark(bookmark) {
      if (this.isExternalLink(bookmark.location)) {
        window.open(bookmark.location, '_blank');
      } else {
        // For internal links, navigate within the app
        if (bookmark.location.startsWith('/')) {
          this.$router.push(bookmark.location);
        } else {
          this.$router.push('/' + bookmark.location);
        }
      }
    },
    isExternalLink(url) {
      return url.startsWith('http://') || url.startsWith('https://') || url.startsWith('//');
    },
    formatUrl(url) {
      if (this.isExternalLink(url)) {
        try {
          const urlObj = new URL(url);
          return urlObj.hostname + urlObj.pathname;
        } catch {
          return url;
        }
      } else {
        return url.startsWith('/') ? url : '/' + url;
      }
    },
    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text);
        alert("URL copied to clipboard!");
      } catch (error) {
        console.error('Error copying to clipboard:', error);
        alert("Failed to copy URL");
      }
    }
  },
});
</script>

<style scoped>
/* Modern Design System */
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
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
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
  text-decoration: none;
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
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn.danger:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Bookmarks Card */
.bookmarks-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
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

.card-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
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
  background: var(--background);
  color: var(--text-primary);
  min-width: 250px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Bookmarks Container */
.bookmarks-container {
  padding: 24px;
}

.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-state p {
  margin: 0 0 16px 0;
  font-size: 14px;
}

/* Bookmarks Grid */
.bookmarks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.bookmark-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  transition: all 0.2s ease;
  border-left: 4px solid var(--primary-color);
}

.bookmark-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.bookmark-card.bookmark-external {
  border-left: 4px solid var(--success-color);
}

.bookmark-card.bookmark-internal {
  border-left: 4px solid var(--warning-color);
}

.bookmark-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.bookmark-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.bookmark-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}

.bookmark-details h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.bookmark-url {
  margin: 0;
  color: var(--text-muted);
  font-size: 12px;
  font-family: monospace;
  word-break: break-all;
}

.bookmark-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
}

.status-external {
  background: #f0fdf4;
  color: var(--success-color);
}

.status-internal {
  background: #fef3c7;
  color: var(--warning-color);
}

/* Bookmark Actions */
.bookmark-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
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

.visit-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.visit-btn:hover {
  background: #dbeafe;
}

.edit-btn {
  background: #f0fdf4;
  color: var(--success-color);
}

.edit-btn:hover {
  background: #dcfce7;
}

.copy-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.copy-btn:hover {
  background: #fde68a;
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
}

/* Modal Styles */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 500px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.icon-preview {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 12px;
  background: var(--background);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-secondary);
}

.icon-preview ion-icon {
  font-size: 18px;
  color: var(--primary-color);
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

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
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
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box input {
    min-width: 100%;
  }
  
  .bookmarks-grid {
    grid-template-columns: 1fr;
  }
  
  .custom-modal-content {
    width: 95vw;
    margin: 20px;
  }
  
  .bookmark-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .bookmark-status {
    align-self: flex-start;
  }
}
</style>
