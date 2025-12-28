import { createRouter, createWebHistory } from 'vue-router';
import { useUserStore } from '@/stores/user';
import { useProjectStore } from '@/stores/project';

const routes = [
  {
    path: '/',
    name: 'home',
    meta: { requiresAuth: true }
  },
  {
    path: '/project/:id',
    name: 'project',
    meta: { requiresAuth: true },
    props: true
  },
  {
    path: '/login',
    name: 'login',
    meta: { requiresAuth: false }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

let initialCheckDone = false;

// Navigation Guards
router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore();
  const projectStore = useProjectStore();
  
  // Beim ersten Laden: Warte bis User-Check abgeschlossen ist
  if (!initialCheckDone) {
    // Warte bis der User-Check in App.vue abgeschlossen ist
    while (userStore.getIsLoading) {
      await new Promise(resolve => setTimeout(resolve, 50));
    }
    initialCheckDone = true;
  }
  
  const isAuthenticated = userStore.getIsAuthenticated;
  const requiresAuth = to.meta.requiresAuth;
  
  // Redirect zu Login wenn nicht authentifiziert
  if (requiresAuth && !isAuthenticated) {
    return next({ name: 'login' });
  }
  
  // Redirect zur Home wenn bereits eingeloggt und zur Login-Seite navigiert wird
  if (to.name === 'login' && isAuthenticated) {
    return next({ name: 'home' });
  }
  
  // Wenn zur Projekt-Route navigiert wird, lade das Projekt
  if (to.name === 'project' && to.params.id) {
    const projectId = parseInt(to.params.id);
    
    // Prüfe ob wir das Projekt bereits geladen haben
    if (!projectStore.getCurrentProject || projectStore.getCurrentProject.id !== projectId) {
      try {
        await projectStore.loadProjectById(projectId);
      } catch (error) {
        console.error('Fehler beim Laden des Projekts:', error);
        // Bei Fehler zurück zur Home
        return next({ name: 'home' });
      }
    }
  }
  
  next();
});

export default router;
