<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="people-outline" title="User Management" />
      <div class="page-container">
        <PageHeader icon="people-outline" title="User Management">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="refreshUsers">Refresh</ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">New User</ActionButton>
          </template>
        </PageHeader>
        <div class="stats-grid">
          <StatCard icon="people-outline" color="primary" :value="data.length" label="Total Users" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="activeUsers" label="Active Users" />
          <StatCard icon="time-outline" color="warning" :value="pendingUsers" label="Pending Verification" />
          <StatCard icon="business-outline" color="info" :value="assignedUsers" label="Project Assigned" />
        </div>
        <DataCard title="All Users" :subtitle="`${filteredUsers.length} user${filteredUsers.length !== 1 ? 's' : ''}`"
          noPadding>
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search users..." />
          </template>
          <div class="table-wrapper">
            <LoadingState v-if="loading" message="Loading users..." />

            <EmptyState v-else-if="filteredUsers.length === 0" icon="people-outline" title="No Users Found"
              :description="searchTerm ? 'No users match your search criteria.' : 'No users have been created yet.'" />

            <div v-else class="modern-table">
              <div class="table-header">
                <div v-for="(label, index) in tableLabels" :key="label" class="header-cell" @click="sortBy(index)">
                  <span class="header-text">{{ label }}</span>
                  <div class="sort-indicator">
                    <ion-icon v-if="sortColumn === index && sortDirection === 'asc'" name="chevron-up-outline"
                      class="sort-active"></ion-icon>
                    <ion-icon v-else-if="sortColumn === index && sortDirection === 'desc'" name="chevron-down-outline"
                      class="sort-active"></ion-icon>
                    <ion-icon v-else name="swap-vertical-outline" class="sort-default"></ion-icon>
                  </div>
                </div>
                <div class="header-cell actions-header">Actions</div>
              </div>
              <div class="table-body">
                <div v-for="user in sortedUsers" :key="user[0]" class="table-row">
                  <div class="table-cell cell-id">
                    <span class="cell-content">{{ user[0] }}</span>
                  </div>
                  <div class="table-cell cell-avatar">
                    <div class="user-avatar" :class="{ 'avatar-initials': user[1] === 'avatar' }"
                      :style="user[1] === 'avatar' ? { backgroundColor: getAvatarColor(user[0]) } : {}">
                      <img v-if="user[1] && user[1] !== 'null' && user[1] !== 'avatar' && user[1] !== 'google'"
                        :src="user[1]" alt="Profile" />
                      <span v-else-if="user[1] === 'avatar'" class="initials">{{ getInitials(user[2], user[3]) }}</span>
                      <ion-icon v-else name="person-outline"></ion-icon>
                    </div>
                  </div>
                  <div class="table-cell cell-name">
                    <div class="user-info">
                      <span class="user-name">{{ user[2] }} {{ user[3] }}</span>
                      <span class="user-email">{{ user[4] }}</span>
                    </div>
                  </div>
                  <div class="table-cell cell-status">
                    <span class="status-badge" :class="{
                      'status-active': user[7] === 'active',
                      'status-pending': user[7] === 'pending_verification',
                      'status-inactive': user[7] !== 'active' && user[7] !== 'pending_verification'
                    }">
                      {{ user[7] }}
                    </span>
                  </div>
                  <div class="table-cell cell-project">
                    <span v-if="user.assignedProject" class="project-badge">
                      {{ user.assignedProject }}
                    </span>
                    <span v-else class="no-project">No Project</span>
                  </div>
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button v-if="user[7] === 'pending_verification'" class="icon-btn approve-btn"
                        @click="approve(user[0])" title="Approve User">
                        <ion-icon name="checkmark-outline"></ion-icon>
                      </button>
                      <button class="icon-btn assign-btn" @click="openAssignModal(user)" title="Assign Project">
                        <ion-icon name="business-outline"></ion-icon>
                      </button>
                      <button class="icon-btn edit-btn" @click="editUser(user)" title="Edit User">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteUser(user[0])" title="Delete User">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </DataCard>
        <DataCard v-if="pendingVerificationEntries.length > 0" title="Pending Verification"
          :subtitle="`${pendingVerificationEntries.length} user${pendingVerificationEntries.length !== 1 ? 's' : ''} waiting`"
          noPadding>
          <div class="pending-users">
            <div v-for="user in pendingVerificationEntries" :key="user[0]" class="pending-user-card">
              <div class="pending-user-info">
                <div class="user-avatar" :class="{ 'avatar-initials': user[1] === 'avatar' }"
                  :style="user[1] === 'avatar' ? { backgroundColor: getAvatarColor(user[0]) } : {}">
                  <img v-if="user[1] && user[1] !== 'null' && user[1] !== 'avatar' && user[1] !== 'google'"
                    :src="user[1]" alt="Profile" />
                  <span v-else-if="user[1] === 'avatar'" class="initials">{{ getInitials(user[2], user[3]) }}</span>
                  <ion-icon v-else name="person-outline"></ion-icon>
                </div>
                <div class="user-details">
                  <h4>{{ user[2] }} {{ user[3] }}</h4>
                  <p>{{ user[4] }}</p>
                </div>
              </div>
              <ActionButton variant="primary" icon="checkmark-outline" @click="approve(user[0])">Approve</ActionButton>
            </div>
          </div>
        </DataCard>
      </div>
      <AppModal v-model="showCreateModal" title="Create New User">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" v-model="newUser.first_name" class="modern-input" placeholder="Enter first name" />
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input type="text" v-model="newUser.last_name" class="modern-input" placeholder="Enter last name" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" v-model="newUser.email_adress" class="modern-input" placeholder="Enter email address" />
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" v-model="newUser.password" class="modern-input" placeholder="Enter password" />
        </div>
        <div class="form-group">
          <label class="form-label">Assign to Project</label>
          <select v-model="newUser.assigned_project" class="modern-select">
            <option value="">No Project Assignment</option>
            <option v-for="project in availableProjects" :key="project.link" :value="project.link">
              {{ project.name }}
            </option>
          </select>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="showCreateModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="createUser()">Create User</ActionButton>
        </template>
      </AppModal>
      <AppModal v-model="showAssignModal" title="Assign Project">
        <div class="assign-user-info">
          <div class="user-avatar" :class="{ 'avatar-initials': selectedUser?.image === 'avatar' }"
            :style="selectedUser?.image === 'avatar' ? { backgroundColor: getAvatarColor(selectedUser.id) } : {}">
            <img
              v-if="selectedUser?.image && selectedUser?.image !== 'null' && selectedUser?.image !== 'avatar' && selectedUser?.image !== 'google'"
              :src="selectedUser.image" alt="Profile" />
            <span v-else-if="selectedUser?.image === 'avatar'" class="initials">{{
              getInitialsFromName(selectedUser.name) }}</span>
            <ion-icon v-else name="person-outline"></ion-icon>
          </div>
          <div class="user-details">
            <h4>{{ selectedUser?.name }}</h4>
            <p>{{ selectedUser?.email }}</p>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Select Project</label>
          <select v-model="assignmentData.project" class="modern-select">
            <option value="">Remove Project Assignment</option>
            <option v-for="project in availableProjects" :key="project.link" :value="project.link">
              {{ project.name }}
            </option>
          </select>
          <p class="form-help">Users assigned to a project will be automatically redirected to that project upon
            login</p>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="showAssignModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="assignProject()">Assign Project</ActionButton>
        </template>
      </AppModal>
      <AppModal v-model="showEditModal" title="Edit User">
        <div class="assign-user-info">
          <div class="user-avatar" :class="{ 'avatar-initials': editUserData.image === 'avatar' }"
            :style="editUserData.image === 'avatar' ? { backgroundColor: getAvatarColor(editUserData.id) } : {}">
            <img
              v-if="editUserData.image && editUserData.image !== 'null' && editUserData.image !== 'avatar' && editUserData.image !== 'google'"
              :src="editUserData.image" alt="Profile" />
            <span v-else-if="editUserData.image === 'avatar'" class="initials">{{
              getInitials(editUserData.first_name, editUserData.last_name) }}</span>
            <ion-icon v-else name="person-outline"></ion-icon>
          </div>
          <div class="user-details">
            <h4>Editing User ID: {{ editUserData.id }}</h4>
            <p>{{ editUserData.original_email }}</p>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" v-model="editUserData.first_name" class="modern-input" placeholder="Enter first name" />
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input type="text" v-model="editUserData.last_name" class="modern-input" placeholder="Enter last name" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" v-model="editUserData.email" class="modern-input" placeholder="Enter email address" />
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select v-model="editUserData.status" class="modern-select">
            <option value="active">Active</option>
            <option value="pending_verification">Pending Verification</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" v-model="editUserData.password" class="modern-input"
            placeholder="Leave empty to keep current password" />
          <div class="form-help">
            Only enter a new password if you want to change it. Leave empty to keep the current password.
          </div>
        </div>
        <template #footer>
          <ActionButton variant="secondary" @click="showEditModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="saveUserEdit" :disabled="loading">
            <ion-spinner v-if="loading" name="crescent"></ion-spinner>
            <ion-icon v-else name="save-outline"></ion-icon>
            <span v-if="!loading">Save Changes</span>
          </ActionButton>
        </template>
      </AppModal>
      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import DataCard from "@/components/DataCard.vue";
import AppModal from "@/components/AppModal.vue";
import { defineComponent, ref, getCurrentInstance } from "vue";

export default defineComponent({
  name: "ManageUsers",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    ActionButton,
    SearchBox,
    LoadingState,
    EmptyState,
    DataCard,
    AppModal,
  },
  setup() {
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const labels = ref([]);
    const data = ref([]);
    const data2 = ref({});
    const pendingVerificationEntries = ref([]);
    const availableProjects = ref([]);

    const loadUsers = async function () {
      try {
        const response = await axios.get(
          "v2/users",
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );


        labels.value = response.data.labels;
        data.value = response.data.data;
        data2.value = response.data;

        const accountStatusIndex = data2.value.labels.indexOf("account_status");
        pendingVerificationEntries.value = data2.value.data.filter(
          (entry) => entry[accountStatusIndex] === "pending_verification"
        );

        await loadUserAssignments();
      } catch (error) {
        console.error('Error loading users:', error);
      }
    };

    const loadUserAssignments = async function () {
      try {
        const response = await axios.get(
          "v2/users/assignments",
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          data.value.forEach(user => {
            const assignment = response.data.assignments.find(a => a.user_id == user[0]);
            if (assignment) {
              user.assignedProject = assignment.project_name;
              user.assignedProjectLink = assignment.project_link;
            } else {
              user.assignedProject = null;
              user.assignedProjectLink = null;
            }
          });
        }
      } catch (error) {
        console.error('Error loading user assignments:', error);
      }
    };

    const loadProjects = async function () {
      try {
        const response = await axios.get(
          "v2/projects",
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          availableProjects.value = response.data.projects;
        }
      } catch (error) {
        console.error('Error loading projects:', error);
      }
    };

    loadUsers();
    loadProjects();

    return {
      labels,
      data,
      pendingVerificationEntries,
      availableProjects,
      loadUsers,
      loadUserAssignments,
      loadProjects,
    };
  },
  data() {
    return {
      loading: false,
      searchTerm: '',
      sortColumn: null,
      sortDirection: 'asc',
      successMessage: '',
      showCreateModal: false,
      showAssignModal: false,
      showEditModal: false,
      selectedUser: null,
      editUserData: {
        id: null,
        first_name: '',
        last_name: '',
        email: '',
        original_email: '',
        status: '',
        password: '',
        image: null
      },
      newUser: {
        first_name: '',
        last_name: '',
        email_adress: '',
        password: '',
        assigned_project: ''
      },
      assignmentData: {
        project: ''
      }
    };
  },
  computed: {
    tableLabels() {
      return ['ID', 'Avatar', 'Name', 'Status', 'Project'];
    },
    filteredUsers() {
      if (!this.searchTerm.trim()) {
        return this.data;
      }

      const searchLower = this.searchTerm.toLowerCase();
      return this.data.filter(user =>
        user[2]?.toLowerCase().includes(searchLower) ||
        user[3]?.toLowerCase().includes(searchLower) ||
        user[4]?.toLowerCase().includes(searchLower) ||
        user[7]?.toLowerCase().includes(searchLower)
      );
    },
    sortedUsers() {
      if (this.sortColumn === null) {
        return this.filteredUsers;
      }

      const sorted = [...this.filteredUsers].sort((a, b) => {
        let aVal = a[this.sortColumn];
        let bVal = b[this.sortColumn];

        if (this.sortColumn === 2) {
          aVal = `${a[2]} ${a[3]}`;
          bVal = `${b[2]} ${b[3]}`;
        }

        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);

        if (!isNaN(aNum) && !isNaN(bNum)) {
          return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          const aStr = String(aVal).toLowerCase();
          const bStr = String(bVal).toLowerCase();

          if (this.sortDirection === 'asc') {
            return aStr.localeCompare(bStr);
          } else {
            return bStr.localeCompare(aStr);
          }
        }
      });

      return sorted;
    },
    activeUsers() {
      return this.data.filter(user => user[7] === 'active').length;
    },
    pendingUsers() {
      return this.pendingVerificationEntries.length;
    },
    assignedUsers() {
      return this.data.filter(user => user.assignedProject).length;
    }
  },
  methods: {
    sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }
    },
    refreshUsers() {
      this.loadUsers();
    },
    async createUser() {
      if (!this.newUser.first_name || !this.newUser.email_adress || !this.newUser.password) {
        alert('Please fill in all required fields');
        return;
      }

      try {
        const response = await this.$axios.post(
          "v2/users",
          this.$qs.stringify({
            first_name: this.newUser.first_name,
            last_name: this.newUser.last_name,
            email_adress: this.newUser.email_adress,
            password: this.newUser.password,
            assigned_project: this.newUser.assigned_project
          }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          this.showSuccessMessage('User created successfully');
          this.showCreateModal = false;
          this.resetNewUser();
          this.loadUsers();
        } else {
          alert('Error creating user: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error creating user:', error);
        alert('Error creating user');
      }
    },
    async approve(userID) {
      try {
        await this.$axios.put(
          `v2/users/${userID}/status`,
          this.$qs.stringify({
            newStatus: "active",
          }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        this.showSuccessMessage(`User ${userID} approved successfully`);
        this.loadUsers();
      } catch (error) {
        console.error('Error approving user:', error);
        alert('Error approving user');
      }
    },
    openAssignModal(user) {
      this.selectedUser = {
        id: user[0],
        name: `${user[2]} ${user[3]}`,
        email: user[4],
        image: user[1]
      };
      this.assignmentData.project = user.assignedProjectLink || '';
      this.showAssignModal = true;
    },
    async assignProject() {
      try {
        const response = await this.$axios.post(
          `v2/users/${this.selectedUser.id}/project`,
          this.$qs.stringify({
            project: this.assignmentData.project
          }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          this.showSuccessMessage('Project assignment updated successfully');
          this.showAssignModal = false;
          this.loadUsers();
        } else {
          alert('Error assigning project: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error assigning project:', error);
        alert('Error assigning project');
      }
    },
    editUser(user) {
      this.editUserData = {
        id: user[0],
        first_name: user[2] || '',
        last_name: user[3] || '',
        email: user[4] || '',
        original_email: user[4] || '',
        status: user[7] || 'active',
        password: '',
        image: user[1]
      };
      this.showEditModal = true;
    },
    async saveUserEdit() {
      if (!this.editUserData.first_name || !this.editUserData.email) {
        alert('Please fill in all required fields');
        return;
      }

      this.loading = true;
      try {
        const updateData = {
          updateUser: "updateUser",
          userID: this.editUserData.id,
          first_name: this.editUserData.first_name,
          last_name: this.editUserData.last_name,
          email_adress: this.editUserData.email,
          account_status: this.editUserData.status
        };

        if (this.editUserData.password.trim()) {
          updateData.password = this.editUserData.password;
        }

        const response = await this.$axios.put(
          `v2/users/${this.editUserData.id}`,
          this.$qs.stringify(updateData),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          this.showSuccessMessage('User updated successfully');
          this.showEditModal = false;
          this.loadUsers();
        } else {
          alert('Error updating user: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error updating user:', error);
        alert('Error updating user');
      } finally {
        this.loading = false;
      }
    },
    async deleteUser(userID) {
      if (!confirm('Are you sure you want to delete this user?')) {
        return;
      }

      try {
        const response = await this.$axios.delete(
          `v2/users/${userID}`,
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          this.showSuccessMessage('User deleted successfully');
          this.loadUsers();
        } else {
          alert('Error deleting user: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error deleting user:', error);
        alert('Error deleting user');
      }
    },
    resetNewUser() {
      this.newUser = {
        first_name: '',
        last_name: '',
        email_adress: '',
        password: '',
        assigned_project: ''
      };
    },
    showSuccessMessage(message) {
      this.successMessage = message;
      setTimeout(() => {
        this.successMessage = '';
      }, 3000);
    },
    getInitials(firstName, lastName) {
      const first = firstName ? firstName.charAt(0).toUpperCase() : '';
      const last = lastName ? lastName.charAt(0).toUpperCase() : '';
      return first + last;
    },
    getInitialsFromName(fullName) {
      if (!fullName) return '';
      const parts = fullName.split(' ');
      if (parts.length >= 2) {
        return parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
      }
      return fullName.charAt(0).toUpperCase();
    },
    getAvatarColor(userId) {
      const colors = [
        '#667eea', '#764ba2', '#f093fb', '#f5576c',
        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
        '#fa709a', '#fee140', '#30cfd0', '#330867',
        '#a8edea', '#fed6e3', '#ff9a9e', '#fecfef'
      ];
      return colors[userId % colors.length];
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.action-group-left,
.action-group-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-info h2 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

.header-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stats-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stats-card .card-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  font-size: 24px;
  color: white;
}

.stats-card.users .card-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stats-card.pending .card-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stats-card.assigned .card-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stats-card h3 {
  margin: 0 0 8px 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stats-card .stats-number {
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 800px;
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
  justify-content: space-between;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.header-cell:hover {
  background: var(--border);
}

.actions-header {
  flex: 0 0 140px;
  justify-content: center;
  cursor: default;
}

.actions-header:hover {
  background: var(--background);
}

.header-text {
  font-weight: 600;
}

.sort-indicator {
  display: flex;
  align-items: center;
  margin-left: 8px;
}

.sort-indicator ion-icon {
  font-size: 14px;
  transition: all 0.2s ease;
}

.sort-default {
  opacity: 0.3;
}

.sort-active {
  opacity: 1;
  color: var(--primary-color);
}

.header-cell:hover .sort-default {
  opacity: 0.6;
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

.actions-cell {
  flex: 0 0 140px;
  justify-content: center;
  padding: 12px 16px;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--background);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 2px solid var(--border) !important;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar ion-icon {
  font-size: 20px;
  color: var(--text-secondary);
}

.user-avatar.avatar-initials {
  border: none;
}

.user-avatar .initials {
  font-size: 14px;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
  user-select: none;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
}

.user-email {
  color: var(--text-secondary);
  font-size: 12px;
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

.status-active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-pending {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(217, 119, 6, 0.2);
}

.status-inactive {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.project-badge {
  display: inline-block;
  padding: 6px 12px;
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid rgba(249, 115, 22, 0.2);
}

.no-project {
  color: var(--text-secondary);
  font-style: italic;
  font-size: 12px;
}

.action-buttons {
  display: flex;
  gap: 8px;
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

.approve-btn {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.approve-btn:hover {
  background: rgba(5, 150, 105, 0.2);
  transform: scale(1.05);
}

.assign-btn {
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
}

.assign-btn:hover {
  background: rgba(249, 115, 22, 0.2);
  transform: scale(1.05);
}

.edit-btn {
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.edit-btn:hover {
  background: rgba(249, 115, 22, 0.22);
  transform: scale(1.05);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
  transform: scale(1.05);
}

.pending-users {
  padding: 24px;
}

.pending-user-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-bottom: 16px;
  transition: all 0.2s ease;
}

.pending-user-card:hover {
  background: var(--surface);
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.pending-user-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.pending-user-info .user-details h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.pending-user-info .user-details p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.modern-edit-form {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

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
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-help {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
}

.assign-user-info {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  margin-bottom: 20px;
}

.assign-user-info .user-details h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.assign-user-info .user-details p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.success-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: rgba(5, 150, 105, 0.95);
  color: white;
  padding: 16px 20px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  z-index: 10001;
  backdrop-filter: blur(8px);
  box-shadow: var(--shadow-lg);
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    flex-wrap: wrap;
    justify-content: center;
  }

  .header-cell,
  .table-cell {
    min-width: 100px;
    padding: 12px 8px;
    font-size: 12px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .pending-user-card {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
}

@media (max-width: 480px) {
  .modern-table {
    min-width: 600px;
  }

  .cell-content {
    max-width: 80px;
  }

  .success-toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}
</style>
