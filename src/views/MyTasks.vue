<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="checkmark-done-outline" title="My Tasks" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>My Tasks</h1>
            <p>Manage and track your tasks and deliverables</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="loadTasks">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="setNewTaskModalOpen(true)">
              <ion-icon name="add-outline"></ion-icon>
              New Task
            </button>
          </div>
        </div>

        <!-- Task Statistics -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="list-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ taskStats.total }}</h3>
              <p>Total Tasks</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon todo">
              <ion-icon name="alert-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ taskStats.todo }}</h3>
              <p>To Do</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon in-progress">
              <ion-icon name="time-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ taskStats.in_progress }}</h3>
              <p>In Progress</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon completed">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ taskStats.completed }}</h3>
              <p>Completed</p>
            </div>
          </div>
        </div>

        <!-- Filter Options -->
        <div class="filter-bar">
          <button 
            v-for="filter in filters" 
            :key="filter.value"
            :class="['filter-btn', { active: selectedFilter === filter.value }]"
            @click="selectedFilter = filter.value; filterTasks()"
          >
            <ion-icon :name="filter.icon"></ion-icon>
            {{ filter.label }}
          </button>
        </div>

        <!-- Tasks List -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>{{ getFilterTitle() }}</h3>
              <span class="entry-count">{{ filteredTasks.length }} task{{ filteredTasks.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="filteredTasks.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="checkmark-done-outline" class="no-data-icon"></ion-icon>
                <h4>No Tasks Found</h4>
                <p>{{ selectedFilter === 'all' ? 'Create your first task to get started!' : `No ${selectedFilter.replace('_', ' ')} tasks found.` }}</p>
                <button class="action-btn primary" @click="setNewTaskModalOpen(true)">
                  <ion-icon name="add-outline"></ion-icon>
                  Create Task
                </button>
              </div>
            </div>

            <div v-else class="tasks-list">
              <div 
                v-for="task in filteredTasks" 
                :key="task.id"
                class="task-card"
                @click="viewTask(task)"
              >
                <div class="task-checkbox">
                  <input
                    type="checkbox"
                    :checked="task.status === 'completed'"
                    @click.stop="toggleTaskStatus(task)"
                    class="custom-checkbox"
                  />
                </div>
                
                <div class="task-content">
                  <div class="task-header">
                    <h4 :class="{ 'task-completed': task.status === 'completed' }">
                      {{ task.title }}
                    </h4>
                    <div class="task-badges">
                      <span :class="['priority-badge', task.priority]">
                        {{ task.priority.toUpperCase() }}
                      </span>
                      <span :class="['status-badge', task.status.replace('_', '-')]">
                        {{ task.status.replace('_', ' ').toUpperCase() }}
                      </span>
                    </div>
                  </div>
                  
                  <p v-if="task.description" class="task-description">
                    {{ task.description.substring(0, 150) }}{{ task.description.length > 150 ? '...' : '' }}
                  </p>
                  
                  <div class="task-meta">
                    <span v-if="task.assigned_name" class="meta-item">
                      <ion-icon name="person-outline"></ion-icon>
                      {{ task.assigned_name }}
                    </span>
                    <span v-if="task.due_date" :class="['meta-item', 'due-date', getDueDateClass(task.due_date)]">
                      <ion-icon name="calendar-outline"></ion-icon>
                      Due: {{ formatDate(task.due_date) }}
                    </span>
                  </div>
                </div>

                <div class="task-actions" @click.stop>
                  <button class="icon-btn" @click="editTask(task)" title="Edit">
                    <ion-icon name="create-outline"></ion-icon>
                  </button>
                  <button class="icon-btn danger" @click="deleteTask(task)" title="Delete">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- New Task Modal -->
      <div v-if="isNewTaskModalOpen" class="custom-modal-overlay" @click="setNewTaskModalOpen(false)">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h2>{{ editingTask ? 'Edit Task' : 'New Task' }}</h2>
            <button class="close-btn" @click="setNewTaskModalOpen(false)">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>

          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Title *</label>
              <input 
                v-model="taskForm.title" 
                placeholder="Enter task title"
                class="modern-input"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea 
                v-model="taskForm.description" 
                placeholder="Enter task description"
                rows="4"
                class="modern-input"
              ></textarea>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Priority</label>
                <select v-model="taskForm.priority" class="modern-select">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>

              <div class="form-group" v-if="editingTask">
                <label class="form-label">Status</label>
                <select v-model="taskForm.status" class="modern-select">
                  <option value="todo">To Do</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Assign to</label>
                <select v-model="taskForm.assigned_to" class="modern-select">
                  <option value="">Unassigned</option>
                  <option 
                    v-for="user in projectUsers" 
                    :key="user.userID" 
                    :value="user.userID"
                  >
                    {{ user.full_name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Due Date</label>
                <input 
                  type="date"
                  v-model="taskForm.due_date" 
                  class="modern-input"
                />
              </div>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="setNewTaskModalOpen(false)">
                Cancel
              </button>
              <button 
                class="action-btn primary" 
                @click="saveTask" 
                :disabled="!taskForm.title"
              >
                {{ editingTask ? 'Update Task' : 'Create Task' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, computed, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import {
  IonPage, IonContent, IonIcon, alertController
} from '@ionic/vue';
import SiteTitle from '@/components/SiteTitle.vue';

export default defineComponent({
  name: 'MyTasks',
  components: {
    IonPage, IonContent, IonIcon, SiteTitle
  },
  setup() {
    const route = useRoute();
    const tasks = ref([]);
    const filteredTasks = ref([]);
    const projectUsers = ref([]);
    const selectedFilter = ref('all');
    const isNewTaskModalOpen = ref(false);
    const editingTask = ref(null);
    
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const filters = [
      { value: 'all', label: 'All Tasks', icon: 'list-outline' },
      { value: 'todo', label: 'To Do', icon: 'alert-circle-outline' },
      { value: 'in_progress', label: 'In Progress', icon: 'time-outline' },
      { value: 'completed', label: 'Completed', icon: 'checkmark-circle-outline' }
    ];

    const taskForm = ref({
      title: '',
      description: '',
      priority: 'medium',
      status: 'todo',
      assigned_to: '',
      due_date: ''
    });

    const taskStats = computed(() => {
      const stats = {
        total: tasks.value.length,
        todo: 0,
        in_progress: 0,
        completed: 0
      };
      
      tasks.value.forEach(task => {
        stats[task.status]++;
      });
      
      return stats;
    });

    const getFilterTitle = () => {
      const filter = filters.find(f => f.value === selectedFilter.value);
      return filter ? filter.label : 'All Tasks';
    };

    const setNewTaskModalOpen = (open) => {
      isNewTaskModalOpen.value = open;
      if (!open) {
        editingTask.value = null;
        resetTaskForm();
      }
    };

    const resetTaskForm = () => {
      taskForm.value = {
        title: '',
        description: '',
        priority: 'medium',
        status: 'todo',
        assigned_to: '',
        due_date: ''
      };
    };

    const initTasks = async () => {
      try {
        await axios.post('tasks.php', qs.stringify({
          initTasks: 'initTasks'
        }));
      } catch (error) {
        console.error('Error initializing tasks:', error);
      }
    };

    const loadTasks = async () => {
      try {
        const response = await axios.post('tasks.php', qs.stringify({
          getTasks: 'getTasks',
          project: route.params.project
        }));
        
        if (response.data.success) {
          tasks.value = response.data.tasks;
          filterTasks();
        }
      } catch (error) {
        console.error('Error loading tasks:', error);
      }
    };

    const loadProjectUsers = async () => {
      try {
        const response = await axios.post('tasks.php', qs.stringify({
          getProjectUsers: 'getProjectUsers',
          project: route.params.project
        }));
        
        if (response.data.success) {
          projectUsers.value = response.data.users;
        }
      } catch (error) {
        console.error('Error loading project users:', error);
      }
    };

    const filterTasks = () => {
      if (selectedFilter.value === 'all') {
        filteredTasks.value = tasks.value;
      } else {
        filteredTasks.value = tasks.value.filter(task => task.status === selectedFilter.value);
      }
    };

    const saveTask = async () => {
      try {
        const formData = { ...taskForm.value };
        
        if (editingTask.value) {
          formData.updateTask = 'updateTask';
          formData.taskID = editingTask.value.id;
        } else {
          formData.createTask = 'createTask';
        }
        
        formData.project = route.params.project;
        
        const response = await axios.post('tasks.php', qs.stringify(formData));
        
        if (response.data.success) {
          setNewTaskModalOpen(false);
          await loadTasks();
        } else {
          alert(response.data.message);
        }
      } catch (error) {
        console.error('Error saving task:', error);
        alert('Error saving task');
      }
    };

    const editTask = (task) => {
      editingTask.value = task;
      taskForm.value = {
        title: task.title,
        description: task.description || '',
        priority: task.priority,
        status: task.status,
        assigned_to: task.assigned_to || '',
        due_date: task.due_date || ''
      };
      setNewTaskModalOpen(true);
    };

    const deleteTask = async (task) => {
      const alert = await alertController.create({
        header: 'Confirm Delete',
        message: `Are you sure you want to delete "${task.title}"?`,
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
                const response = await axios.post('tasks.php', qs.stringify({
                  deleteTask: 'deleteTask',
                  taskID: task.id
                }));
                
                if (response.data.success) {
                  await loadTasks();
                } else {
                  alert(response.data.message);
                }
              } catch (error) {
                console.error('Error deleting task:', error);
                alert('Error deleting task');
              }
            }
          }
        ]
      });
      
      await alert.present();
    };

    const toggleTaskStatus = async (task) => {
      const newStatus = task.status === 'completed' ? 'todo' : 'completed';
      
      try {
        const response = await axios.post('tasks.php', qs.stringify({
          updateTask: 'updateTask',
          taskID: task.id,
          status: newStatus
        }));
        
        if (response.data.success) {
          await loadTasks();
        }
      } catch (error) {
        console.error('Error updating task status:', error);
      }
    };

    const viewTask = (task) => {
      editTask(task);
    };

    const getDueDateClass = (dueDate) => {
      const today = new Date();
      const due = new Date(dueDate);
      const diffTime = due - today;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays < 0) return 'overdue';
      if (diffDays <= 1) return 'urgent';
      return 'normal';
    };

    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    onMounted(async () => {
      await initTasks();
      await loadTasks();
      await loadProjectUsers();
    });

    return {
      tasks,
      filteredTasks,
      projectUsers,
      selectedFilter,
      isNewTaskModalOpen,
      editingTask,
      taskForm,
      taskStats,
      filters,
      getFilterTitle,
      setNewTaskModalOpen,
      loadTasks,
      filterTasks,
      saveTask,
      editTask,
      deleteTask,
      toggleTaskStatus,
      viewTask,
      getDueDateClass,
      formatDate
    };
  }
});
</script>

<style scoped>
.modern-content {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
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
  flex-wrap: wrap;
}

/* Action Buttons */
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
}

.action-btn ion-icon {
  font-size: 16px;
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
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 28px;
  flex-shrink: 0;
}

.stat-icon.todo {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.in-progress {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.stat-icon.completed {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Filter Bar */
.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.filter-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.filter-btn ion-icon {
  font-size: 16px;
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
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
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Tasks List */
.tasks-list {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.task-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  transition: all 0.2s ease;
  cursor: pointer;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.task-checkbox {
  padding-top: 4px;
}

.custom-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: var(--primary-color);
}

.task-content {
  flex: 1;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.task-header h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.4;
}

.task-header h4.task-completed {
  text-decoration: line-through;
  opacity: 0.6;
}

.task-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.priority-badge,
.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-badge.low {
  background: #e0f2fe;
  color: #0369a1;
}

.priority-badge.medium {
  background: #dbeafe;
  color: #1d4ed8;
}

.priority-badge.high {
  background: #fed7aa;
  color: #c2410c;
}

.priority-badge.urgent {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.todo {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-badge.in-progress {
  background: #ede9fe;
  color: #7c3aed;
}

.status-badge.completed {
  background: #d1fae5;
  color: #059669;
}

.task-description {
  margin: 8px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.task-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-size: 13px;
}

.meta-item ion-icon {
  font-size: 16px;
}

.meta-item.due-date.overdue {
  color: #dc2626;
  font-weight: 600;
}

.meta-item.due-date.urgent {
  color: #f59e0b;
  font-weight: 600;
}

.task-actions {
  display: flex;
  gap: 8px;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  background: var(--background);
  color: var(--text-primary);
  border-color: var(--text-primary);
}

.icon-btn.danger:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #dc2626;
}

.icon-btn ion-icon {
  font-size: 18px;
}

/* Modal */
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
  padding: 20px;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
}

.custom-modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

.close-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius);
  background: var(--background);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.close-btn ion-icon {
  font-size: 24px;
}

.custom-modal-body {
  padding: 24px;
}

/* Form Elements */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

textarea.modern-input {
  resize: vertical;
  min-height: 100px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Animations */
@keyframes modalFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
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

  .header-actions {
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .task-card {
    flex-direction: column;
  }

  .task-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
  }
}

/* Dark Mode Support */
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
