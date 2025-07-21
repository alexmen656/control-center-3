<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-title>My Tasks</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="setNewTaskModalOpen(true)">
            <ion-icon :icon="add"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    
    <ion-content class="ion-padding">
      <!-- Task Statistics -->
      <ion-card>
        <ion-card-content>
          <ion-grid>
            <ion-row>
              <ion-col size="3" class="text-center">
                <div class="stat-number">{{ taskStats.total }}</div>
                <div class="stat-label">Total</div>
              </ion-col>
              <ion-col size="3" class="text-center">
                <div class="stat-number todo">{{ taskStats.todo }}</div>
                <div class="stat-label">To Do</div>
              </ion-col>
              <ion-col size="3" class="text-center">
                <div class="stat-number in-progress">{{ taskStats.in_progress }}</div>
                <div class="stat-label">In Progress</div>
              </ion-col>
              <ion-col size="3" class="text-center">
                <div class="stat-number completed">{{ taskStats.completed }}</div>
                <div class="stat-label">Completed</div>
              </ion-col>
            </ion-row>
          </ion-grid>
        </ion-card-content>
      </ion-card>

      <!-- Filter Options -->
      <ion-segment v-model="selectedFilter" @ionChange="filterTasks">
        <ion-segment-button value="all">
          <ion-label>All</ion-label>
        </ion-segment-button>
        <ion-segment-button value="todo">
          <ion-label>To Do</ion-label>
        </ion-segment-button>
        <ion-segment-button value="in_progress">
          <ion-label>In Progress</ion-label>
        </ion-segment-button>
        <ion-segment-button value="completed">
          <ion-label>Completed</ion-label>
        </ion-segment-button>
      </ion-segment>

      <!-- Tasks List -->
      <div v-if="filteredTasks.length === 0" class="empty-state">
        <ion-icon :icon="checkboxOutline" size="large"></ion-icon>
        <h3>No tasks found</h3>
        <p>Create your first task to get started!</p>
      </div>

      <ion-list v-else>
        <ion-item-sliding v-for="task in filteredTasks" :key="task.id">
          <ion-item @click="viewTask(task)">
            <ion-checkbox
              slot="start"
              :checked="task.status === 'completed'"
              @ionChange="toggleTaskStatus(task)"
            ></ion-checkbox>
            
            <ion-label>
              <h2 :class="{ 'task-completed': task.status === 'completed' }">
                {{ task.title }}
              </h2>
              <p v-if="task.description">{{ task.description.substring(0, 100) }}...</p>
              <p>
                <ion-chip :color="getPriorityColor(task.priority)" outline>
                  {{ task.priority.toUpperCase() }}
                </ion-chip>
                <ion-chip v-if="task.assigned_name" color="secondary" outline>
                  {{ task.assigned_name }}
                </ion-chip>
                <ion-chip v-if="task.due_date" :color="getDueDateColor(task.due_date)" outline>
                  Due: {{ formatDate(task.due_date) }}
                </ion-chip>
              </p>
            </ion-label>
            
            <ion-badge slot="end" :color="getStatusColor(task.status)">
              {{ task.status.replace('_', ' ').toUpperCase() }}
            </ion-badge>
          </ion-item>
          
          <ion-item-options>
            <ion-item-option @click="editTask(task)" color="primary">
              <ion-icon :icon="create"></ion-icon>
            </ion-item-option>
            <ion-item-option @click="deleteTask(task)" color="danger">
              <ion-icon :icon="trash"></ion-icon>
            </ion-item-option>
          </ion-item-options>
        </ion-item-sliding>
      </ion-list>

      <!-- New Task Modal -->
      <ion-modal :is-open="isNewTaskModalOpen" @will-dismiss="setNewTaskModalOpen(false)">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ editingTask ? 'Edit Task' : 'New Task' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="setNewTaskModalOpen(false)">Close</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content class="ion-padding">
          <ion-item>
            <ion-label position="stacked">Title *</ion-label>
            <ion-input v-model="taskForm.title" placeholder="Enter task title"></ion-input>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Description</ion-label>
            <ion-textarea 
              v-model="taskForm.description" 
              placeholder="Enter task description"
              rows="3"
            ></ion-textarea>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Priority</ion-label>
            <ion-select v-model="taskForm.priority">
              <ion-select-option value="low">Low</ion-select-option>
              <ion-select-option value="medium">Medium</ion-select-option>
              <ion-select-option value="high">High</ion-select-option>
              <ion-select-option value="urgent">Urgent</ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item v-if="editingTask">
            <ion-label position="stacked">Status</ion-label>
            <ion-select v-model="taskForm.status">
              <ion-select-option value="todo">To Do</ion-select-option>
              <ion-select-option value="in_progress">In Progress</ion-select-option>
              <ion-select-option value="completed">Completed</ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Assign to</ion-label>
            <ion-select v-model="taskForm.assigned_to">
              <ion-select-option value="">Unassigned</ion-select-option>
              <ion-select-option 
                v-for="user in projectUsers" 
                :key="user.userID" 
                :value="user.userID"
              >
                {{ user.full_name }}
              </ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Due Date</ion-label>
            <ion-datetime 
              v-model="taskForm.due_date" 
              presentation="date"
            ></ion-datetime>
          </ion-item>
          
          <ion-button 
            expand="block" 
            @click="saveTask" 
            :disabled="!taskForm.title"
            class="ion-margin-top"
          >
            {{ editingTask ? 'Update Task' : 'Create Task' }}
          </ion-button>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, computed, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonButtons,
  IonIcon, IonCard, IonCardContent, IonGrid, IonRow, IonCol, IonSegment,
  IonSegmentButton, IonLabel, IonList, IonItem, IonItemSliding, IonItemOptions,
  IonItemOption, IonCheckbox, IonChip, IonBadge, IonModal, IonInput, IonTextarea,
  IonSelect, IonSelectOption, IonDatetime, alertController
} from '@ionic/vue';
import { 
  add, create, trash, checkboxOutline 
} from 'ionicons/icons';

export default defineComponent({
  name: 'MyTasks',
  components: {
    IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonButtons,
    IonIcon, IonCard, IonCardContent, IonGrid, IonRow, IonCol, IonSegment,
    IonSegmentButton, IonLabel, IonList, IonItem, IonItemSliding, IonItemOptions,
    IonItemOption, IonCheckbox, IonChip, IonBadge, IonModal, IonInput, IonTextarea,
    IonSelect, IonSelectOption, IonDatetime
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

    const getPriorityColor = (priority) => {
      switch (priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'primary';
        case 'low': return 'medium';
        default: return 'medium';
      }
    };

    const getStatusColor = (status) => {
      switch (status) {
        case 'completed': return 'success';
        case 'in_progress': return 'warning';
        case 'todo': return 'primary';
        default: return 'medium';
      }
    };

    const getDueDateColor = (dueDate) => {
      const today = new Date();
      const due = new Date(dueDate);
      const diffTime = due - today;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays < 0) return 'danger';
      if (diffDays <= 1) return 'warning';
      return 'medium';
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
      setNewTaskModalOpen,
      loadTasks,
      filterTasks,
      saveTask,
      editTask,
      deleteTask,
      toggleTaskStatus,
      viewTask,
      getPriorityColor,
      getStatusColor,
      getDueDateColor,
      formatDate,
      // Icons
      add,
      create,
      trash,
      checkboxOutline
    };
  }
});
</script>

<style scoped>
.stat-number {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.25rem;
}

.stat-number.todo {
  color: var(--ion-color-primary);
}

.stat-number.in-progress {
  color: var(--ion-color-warning);
}

.stat-number.completed {
  color: var(--ion-color-success);
}

.stat-label {
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.text-center {
  text-align: center;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--ion-color-medium);
}

.empty-state ion-icon {
  margin-bottom: 1rem;
}

.task-completed {
  text-decoration: line-through;
  opacity: 0.6;
}

ion-chip {
  margin-right: 0.5rem;
}
</style>
