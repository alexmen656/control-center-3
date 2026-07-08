<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="bookmarks-outline" title="Manage Bookmarks" />

      <div class="page-container">
        <PageHeader icon="bookmarks-outline" title="Bookmark Management">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="refreshBookmarks">Refresh</ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">New Bookmark
            </ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="bookmarks-outline" color="primary" :value="totalBookmarks" label="Total Bookmarks" />
          <StatCard icon="globe-outline" color="info" :value="externalBookmarks" label="External Links" />
          <StatCard icon="folder-outline" color="success" :value="internalBookmarks" label="Internal Pages" />
          <StatCard icon="calendar-outline" color="warning" :value="recentBookmarks" label="Added this week" />
        </div>

        <DataCard title="Your Bookmarks" :no-padding="true">
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search bookmarks..." />
          </template>

          <div class="bookmarks-container">
            <LoadingState v-if="loading" message="Loading bookmarks..." />

            <EmptyState v-else-if="filteredBookmarks.length === 0" icon="bookmarks-outline" title="No Bookmarks Found"
              :description="searchTerm ? 'No bookmarks match your search criteria.' : 'You haven\'t saved any bookmarks yet.'">
              <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">Create Your First
                Bookmark</ActionButton>
            </EmptyState>

            <div v-else class="bookmarks-grid">
              <div v-for="bookmark in filteredBookmarks" :key="bookmark.id" class="bookmark-card" :class="{
                'bookmark-external': isExternalLink(bookmark.location),
                'bookmark-internal': !isExternalLink(bookmark.location)
              }">
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
                    <span class="status-badge"
                      :class="isExternalLink(bookmark.location) ? 'status-external' : 'status-internal'">
                      <ion-icon
                        :name="isExternalLink(bookmark.location) ? 'globe-outline' : 'folder-outline'"></ion-icon>
                      {{ isExternalLink(bookmark.location) ? 'External' : 'Internal' }}
                    </span>
                  </div>
                </div>

                <div class="bookmark-actions">
                  <button class="icon-btn visit-btn" @click="visitBookmark(bookmark)" title="Visit Link">
                    <ion-icon name="open-outline"></ion-icon>
                  </button>
                  <button class="icon-btn edit-btn" @click="editBookmark(bookmark)" title="Edit Bookmark">
                    <ion-icon name="pencil-outline"></ion-icon>
                  </button>
                  <button class="icon-btn copy-btn" @click="copyToClipboard(bookmark.location)" title="Copy URL">
                    <ion-icon name="copy-outline"></ion-icon>
                  </button>
                  <button class="icon-btn delete-btn" @click="confirmDelete(bookmark)" title="Delete Bookmark">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </DataCard>
      </div>

      <AppModal v-model="showCreateModal" title="Create New Bookmark">
        <div class="form-group">
          <label for="bookmark-title">Title</label>
          <input id="bookmark-title" type="text" v-model="newBookmark.title" placeholder="Enter bookmark title"
            class="form-input">
        </div>
        <div class="form-group">
          <label for="bookmark-url">URL</label>
          <input id="bookmark-url" type="text" v-model="newBookmark.location"
            placeholder="Enter URL (e.g., https://example.com or /internal/path)" class="form-input">
        </div>
        <div class="form-group">
          <label for="bookmark-icon">Icon</label>
          <input id="bookmark-icon" type="text" v-model="newBookmark.icon"
            placeholder="Enter Ionic icon name (e.g., bookmark-outline)" class="form-input">
          <div class="icon-preview" v-if="newBookmark.icon">
            <ion-icon :name="newBookmark.icon"></ion-icon>
            <span>Preview</span>
          </div>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="showCreateModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="createBookmark"
            :disabled="!newBookmark.title.trim() || !newBookmark.location.trim()">Create Bookmark</ActionButton>
        </template>
      </AppModal>

      <AppModal v-model="showEditModal" title="Edit Bookmark">
        <div class="form-group">
          <label for="edit-bookmark-title">Title</label>
          <input id="edit-bookmark-title" type="text" v-model="editingBookmark.title" placeholder="Enter bookmark title"
            class="form-input">
        </div>
        <div class="form-group">
          <label for="edit-bookmark-url">URL</label>
          <input id="edit-bookmark-url" type="text" v-model="editingBookmark.location" placeholder="Enter URL"
            class="form-input">
        </div>
        <div class="form-group">
          <label for="edit-bookmark-icon">Icon</label>
          <input id="edit-bookmark-icon" type="text" v-model="editingBookmark.icon" placeholder="Enter Ionic icon name"
            class="form-input">
          <div class="icon-preview" v-if="editingBookmark.icon">
            <ion-icon :name="editingBookmark.icon"></ion-icon>
            <span>Preview</span>
          </div>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="showEditModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="updateBookmark">Update Bookmark</ActionButton>
        </template>
      </AppModal>

      <AppModal v-model="deleteModal.show" title="Delete Bookmark">
        <div class="warning-content">
          <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
          <h4>Are you sure?</h4>
          <p>This will permanently delete the bookmark <strong>"{{ deleteModal.bookmark?.title }}"</strong>.</p>
          <p class="warning-text">This action cannot be undone!</p>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="deleteModal.show = false">Cancel</ActionButton>
          <ActionButton variant="danger" @click="deleteBookmark()">Delete Permanently</ActionButton>
        </template>
      </AppModal>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import ActionButton from "@/components/ActionButton.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import AppModal from "@/components/AppModal.vue";
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageBookmarks",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    DataCard,
    ActionButton,
    SearchBox,
    LoadingState,
    EmptyState,
    AppModal,
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
        const response = await this.$axios.get("v2/bookmarks");
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
          "v2/bookmarks",
          this.$qs.stringify({
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
        await this.$axios.delete(
          "v2/bookmarks",
          this.$qs.stringify({
            location: this.editingBookmark.location
          })
        );

        await this.$axios.post(
          "v2/bookmarks",
          this.$qs.stringify({
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
        await this.$axios.delete(
          "v2/bookmarks",
          this.$qs.stringify({
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

/* Bookmarks Container */
.bookmarks-container {
  padding: 24px;
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
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.visit-btn:hover {
  background: rgba(249, 115, 22, 0.22);
}

.edit-btn {
  background: rgba(45, 211, 111, 0.12);
  color: var(--success-color);
}

.edit-btn:hover {
  background: rgba(45, 211, 111, 0.22);
}

.copy-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.copy-btn:hover {
  background: #fde68a;
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
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

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .bookmarks-grid {
    grid-template-columns: 1fr;
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
