<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Header Section -->
        <div class="page-header">
          <div class="header-content">
            <div class="header-info">
              <h1 class="page-title">
                <ion-icon name="library-outline"></ion-icon>
                Manage Pages
              </h1>
              <p class="page-subtitle">Create and organize website pages with custom URLs and icons</p>
            </div>
            <div class="header-stats">
              <div class="stat-card">
                <span class="stat-number">{{ pages.length }}</span>
                <span class="stat-label">Total Pages</span>
              </div>
              <div class="stat-card">
                <span class="stat-number">{{ currentPage }}</span>
                <span class="stat-label">Current Page</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn primary" @click="scrollToForm">
              <ion-icon name="add-outline"></ion-icon>
              <span>New Page</span>
            </button>
          </div>
        </div>

        <!-- Add New Page Form -->
        <div class="data-card add-page-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Create New Page</h3>
              <span class="form-description">Add a new page to your website with custom configuration</span>
            </div>
          </div>

          <div class="card-content">
            <div class="modern-form">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Page Title *</label>
                  <input v-model="title" type="text" placeholder="Enter a descriptive page title..."
                    class="modern-input" required />
                </div>

                <div class="form-group">
                  <label class="form-label">URL Path *</label>
                  <input v-model="url" type="text" placeholder="e.g., /about, /contact, /services" class="modern-input"
                    required />
                </div>

                <div class="form-group">
                  <label class="form-label">Icon Name</label>
                  <input v-model="icon" type="text" placeholder="e.g., home-outline, person-outline, settings-outline"
                    class="modern-input" />
                  <div class="form-help">
                    <span>Use Ionic icon names. Leave empty for default icon.</span>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button class="action-btn primary" @click="submitPage" :disabled="!title || !url">
                  <ion-icon name="add-outline"></ion-icon>
                  Add Page
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Existing Pages -->
        <div class="data-card pages-list-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Existing Pages</h3>
              <span class="pages-count">{{ pages.length }} pages found</span>
            </div>
            <div class="header-right">
              <div class="pagination-info">
                Page {{ currentPage }} of {{ totalPages }}
              </div>
            </div>
          </div>

          <div class="card-content">
            <div v-if="pages.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="library-outline" class="no-data-icon"></ion-icon>
                <h4>No pages found</h4>
                <p>Create your first page to get started with building your website structure.</p>
              </div>
            </div>

            <div v-else class="pages-list">
              <div v-for="(page, index) in pages" :key="index" class="page-item">
                <div class="page-icon">
                  <ion-icon :name="page.icon || 'document-outline'"></ion-icon>
                </div>
                <div class="page-info">
                  <h4 class="page-title">{{ page.title }}</h4>
                  <p class="page-url">{{ page.url }}</p>
                </div>
                <div class="page-actions">
                  <button class="page-action-btn" title="Edit page">
                    <ion-icon name="create-outline"></ion-icon>
                  </button>
                  <button class="page-action-btn danger" title="Delete page">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination Controls -->
            <div v-if="totalPages > 1" class="pagination-controls">
              <button class="pagination-btn" @click="prevPage" :disabled="currentPage <= 1">
                <ion-icon name="chevron-back-outline"></ion-icon>
                Previous
              </button>

              <div class="page-numbers">
                <span class="current-page">{{ currentPage }}</span>
                <span class="page-separator">of</span>
                <span class="total-pages">{{ totalPages }}</span>
              </div>

              <button class="pagination-btn" @click="nextPage" :disabled="currentPage >= totalPages">
                Next
                <ion-icon name="chevron-forward-outline"></ion-icon>
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: "ManagePages",
  data() {
    return {
      // Fields for adding a new page
      title: "",
      url: "",
      icon: "",
      // Pagination for existing pages
      pages: [],
      currentPage: 1,
      totalPages: 1,
      limit: 30,
    };
  },
  mounted() {
    this.loadPages();
  },
  methods: {
    loadPages() {
      this.$axios
        .get(`new_page.php?page=${this.currentPage}`)
        .then((response) => {
          if (response.data.status === "success") {
            this.pages = response.data.data;
            this.totalPages = response.data.total_pages;
          } else {
            alert("Error loading pages");
          }
        })
        .catch((error) => {
          console.error(error);
          alert("Error loading pages");
        });
    },

    scrollToForm() {
      const formElement = document.querySelector('.add-page-card');
      if (formElement) {
        formElement.scrollIntoView({ behavior: 'smooth' });
      }
    },
    submitPage() {
      if (this.title && this.url) {
        // Always pass an empty string for html content
        const postData = this.$qs.stringify({
          addPage: "addPage",
          title: this.title,
          url: this.url,
          icon: this.icon,
          html: ""
        });
        this.$axios
          .post("new_page.php", postData)
          .then((response) => {
            if (response.data.status === "success") {
              alert("Page added successfully!");
              // Reset fields and reload pages
              this.title = "";
              this.url = "";
              this.icon = "";
              // Optionally, go to the first page when a new item is added
              this.currentPage = 1;
              this.loadPages();
            } else {
              alert(response.data.message);
            }
          })
          .catch((error) => {
            console.error(error);
            alert("Error adding page");
          });
      } else {
        alert("Title and URL are required!");
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.loadPages();
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.loadPages();
      }
    }
  }
};
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
  background: var(--background);
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 24px;
}

.header-info {
  flex: 1;
  min-width: 300px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.025em;
}

.page-title ion-icon {
  font-size: 36px;
  color: var(--primary-color);
}

.page-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-stats {
  display: flex;
  gap: 16px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  min-width: 100px;
}

.stat-number {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary-color);
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
  text-align: center;
}

/* Action Bar */
.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.action-group-left {
  display: flex;
  align-items: center;
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

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 32px;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(135deg, var(--background), var(--surface));
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.form-description,
.pages-count {
  color: var(--text-secondary);
  font-size: 14px;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.card-content {
  padding: 24px;
}

/* Modern Form */
.modern-form {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input {
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-help {
  margin-top: 6px;
}

.form-help span {
  color: var(--text-muted);
  font-size: 12px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
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
  line-height: 1.5;
}

/* Pages List */
.pages-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.page-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.page-item:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.page-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--primary-color);
}

.page-icon ion-icon {
  font-size: 24px;
}

.page-info {
  flex: 1;
  min-width: 0;
}

.page-item .page-title {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
}

.page-url {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

.page-actions {
  display: flex;
  gap: 8px;
}

.page-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.page-action-btn:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.page-action-btn.danger:hover {
  color: var(--danger-color);
  border-color: var(--danger-color);
  background: rgba(220, 38, 38, 0.05);
}

/* Pagination Controls */
.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: var(--text-secondary);
}

.current-page {
  font-weight: 600;
  color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .page-title {
    font-size: 28px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .card-header,
  .card-content {
    padding: 20px;
  }

  .page-item {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .page-actions {
    justify-content: center;
  }

  .pagination-controls {
    flex-direction: column;
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .page-container {
    padding: 12px;
  }

  .page-title {
    font-size: 24px;
  }

  .card-header,
  .card-content {
    padding: 16px;
  }

  .header-stats {
    flex-direction: column;
    width: 100%;
  }

  .stat-card {
    flex-direction: row;
    gap: 12px;
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