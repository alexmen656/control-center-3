<template>
  <ion-page>
    <ion-content class="modern-content" :scroll-y="true">
      <SiteTitle icon="grid-outline" title="Dashboard" />
      <div class="projects-dashboard">
        <div class="page-header">
          <h1 class="page-title">
            <ion-icon name="grid-outline"></ion-icon>
            Projects
          </h1>
          <p class="page-subtitle">All your projects in one place</p>
        </div>
        <div class="dashboard-toolbar">
          <div class="search-wrapper">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input type="text" v-model="searchTerm" class="search-input" placeholder="Search Projects..." />
          </div>
          <button class="new-project-btn" @click="navigateTo('/new/project/')">
            <ion-icon name="add-outline"></ion-icon>
            <span>Add New</span>
          </button>
        </div>
        <div v-if="loading" class="projects-grid">
          <div v-for="n in 6" :key="'skeleton-' + n" class="project-card skeleton">
            <div class="card-top">
              <div class="skeleton-avatar"></div>
              <div class="skeleton-lines">
                <div class="skeleton-line w-60"></div>
                <div class="skeleton-line w-40"></div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="filteredProjects.length === 0" class="empty-state">
          <ion-icon name="folder-open-outline"></ion-icon>
          <h3>{{ searchTerm ? 'No matching projects' : 'No projects yet' }}</h3>
          <p>
            {{ searchTerm
              ? 'Try a different search term.'
              : 'Create your first project to get started.' }}
          </p>
          <button v-if="!searchTerm" class="new-project-btn" @click="navigateTo('/new/project/')">
            <ion-icon name="add-outline"></ion-icon>
            <span>Add New</span>
          </button>
        </div>
        <div v-else class="projects-grid">
          <div v-for="project in filteredProjects" :key="project.id" class="project-card" @click="openProject(project)">
            <div class="card-top">
              <div class="project-avatar">
                <ion-icon :name="project.icon || 'folder-outline'"></ion-icon>
              </div>
              <div class="project-meta">
                <h3 class="project-name">{{ project.name }}</h3>
                <div class="project-link">
                  <ion-icon name="link-outline"></ion-icon>
                  <span>{{ project.link }}</span>
                </div>
              </div>
              <ion-icon name="chevron-forward-outline" class="card-arrow"></ion-icon>
            </div>
            <div class="card-footer">
              <span class="footer-tag">
                <ion-icon name="cube-outline"></ion-icon>
                Project
              </span>
              <span class="footer-open">Open</span>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";

interface Project {
  id: string | number;
  name: string;
  link: string;
  icon?: string;
}

export default defineComponent({
  name: "PageView",
  components: {
    SiteTitle,
  },
  data() {
    return {
      projects: [] as Project[],
      loading: true,
      searchTerm: "",
    };
  },
  computed: {
    filteredProjects(): Project[] {
      const term = this.searchTerm.trim().toLowerCase();
      if (!term) return this.projects;
      return this.projects.filter(
        (p) =>
          (p.name || "").toLowerCase().includes(term) ||
          (p.link || "").toLowerCase().includes(term)
      );
    },
  },
  mounted() {
    this.loadProjects();
  },
  methods: {
    async loadProjects() {
      this.loading = true;
      try {
        const response = await this.$axios.get("projects.php");
        this.projects = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        console.error("Error loading projects:", error);
        this.projects = [];
      } finally {
        this.loading = false;
      }
    },
    openProject(project: Project) {
      this.$router.push("/project/" + project.link);
    },
    navigateTo(path: string) {
      this.$router.push(path);
    },
  },
});
</script>

<style scoped>
.modern-content {
  --background: #f8fafc;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
  }
}

.projects-dashboard {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --background: #f8fafc;
  --surface: #ffffff;
  --surface-hover: #f9fafb;
  --border: #e2e8f0;
  --border-strong: #cbd5e1;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.06);
  --shadow-md: 0 4px 12px -2px rgb(0 0 0 / 0.12);
  --radius: 10px;
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  background: var(--background);
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 6px 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--text-primary);
}

.page-title ion-icon {
  font-size: 28px;
  color: var(--primary-color);
}

.page-subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

.dashboard-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 220px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 18px;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 11px 14px 11px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.search-input::placeholder {
  color: var(--text-muted);
}

.search-input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
}

.new-project-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 11px 18px;
  border: none;
  border-radius: var(--radius);
  background: var(--primary-color);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.15s ease;
  white-space: nowrap;
}

.new-project-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.new-project-btn ion-icon {
  font-size: 18px;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.project-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 18px;
  padding: 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease,
    transform 0.15s ease;
}

.project-card:not(.skeleton):hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.card-top {
  display: flex;
  align-items: center;
  gap: 14px;
}

.project-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 10px;
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
}

.project-avatar ion-icon {
  font-size: 22px;
}

.project-meta {
  flex: 1;
  min-width: 0;
}

.project-name {
  margin: 0 0 4px 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.project-link {
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--text-secondary);
  font-size: 13px;
  min-width: 0;
}

.project-link ion-icon {
  font-size: 14px;
  flex-shrink: 0;
}

.project-link span {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-arrow {
  color: var(--text-muted);
  font-size: 18px;
  flex-shrink: 0;
  transition: transform 0.15s ease, color 0.15s ease;
}

.project-card:hover .card-arrow {
  color: var(--primary-color);
  transform: translateX(3px);
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}

.footer-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-muted);
}

.footer-tag ion-icon {
  font-size: 14px;
}

.footer-open {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  opacity: 0;
  transition: opacity 0.15s ease, color 0.15s ease;
}

.project-card:hover .footer-open {
  opacity: 1;
  color: var(--primary-color);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 80px 20px;
  color: var(--text-secondary);
}

.empty-state ion-icon {
  font-size: 56px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 6px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state p {
  margin: 0 0 20px 0;
  font-size: 14px;
}

.project-card.skeleton {
  cursor: default;
}

.skeleton-avatar {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  flex-shrink: 0;
  background: var(--border);
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-lines {
  flex: 1;
}

.skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: var(--border);
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-line.w-60 {
  width: 60%;
  margin-bottom: 8px;
}

.skeleton-line.w-40 {
  width: 40%;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.5;
  }
}

@media (max-width: 768px) {
  .projects-dashboard {
    padding: 16px;
  }

  .projects-grid {
    grid-template-columns: 1fr;
  }
}

@media (prefers-color-scheme: dark) {
  .projects-dashboard {
    --background: #121212;
    --surface: #1a1a1a;
    --surface-hover: #222222;
    --border: #2a2a2a;
    --border-strong: #3a3a3a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.4);
    --shadow-md: 0 4px 12px -2px rgb(0 0 0 / 0.5);
  }
}
</style>
