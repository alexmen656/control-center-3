<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="people-outline" title="User Management" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>User Management</h1>
            <p>Manage users, permissions and project assignments</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshUsers">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="showCreateModal = true">
              <ion-icon name="add-outline"></ion-icon>
              New User
            </button>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="people-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ data.length }}</h3>
              <p>Total Users</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ activeUsers }}</h3>
              <p>Active Users</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="time-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ pendingUsers }}</h3>
              <p>Pending Verification</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="business-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ assignedUsers }}</h3>
              <p>Project Assigned</p>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>All Users</h3>
              <span class="entry-count">{{ filteredUsers.length }} user{{ filteredUsers.length !== 1 ? 's' : ''
                }}</span>
            </div>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Search users..." v-model="searchTerm">
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading users...</p>
            </div>

            <div v-else-if="filteredUsers.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="people-outline" class="no-data-icon"></ion-icon>
                <h4>No Users Found</h4>
                <p>{{ searchTerm ? 'No users match your search criteria.' : 'No users have been created yet.' }}</p>
              </div>
            </div>

            <div v-else class="modern-table">
              <!-- Table Header -->
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

              <!-- Table Body -->
              <div class="table-body">
                <div v-for="user in sortedUsers" :key="user[0]" class="table-row">
                  <!-- User ID (hidden on mobile) -->
                  <div class="table-cell cell-id">
                    <span class="cell-content">{{ user[0] }}</span>
                  </div>

                  <!-- Profile Image -->
                  <div class="table-cell cell-avatar">
                    <div class="user-avatar" :class="{ 'avatar-initials': user[1] === 'avatar' }" 
                         :style="user[1] === 'avatar' ? { backgroundColor: getAvatarColor(user[0]) } : {}">
                      <img v-if="user[1] && user[1] !== 'null' && user[1] !== 'avatar' && user[1] !== 'google'" :src="user[1]" alt="Profile" />
                      <span v-else-if="user[1] === 'avatar'" class="initials">{{ getInitials(user[2], user[3]) }}</span>
                      <ion-icon v-else name="person-outline"></ion-icon>
                    </div>
                  </div>

                  <!-- Name -->
                  <div class="table-cell cell-name">
                    <div class="user-info">
                      <span class="user-name">{{ user[2] }} {{ user[3] }}</span>
                      <span class="user-email">{{ user[4] }}</span>
                    </div>
                  </div>

                  <!-- Status -->
                  <div class="table-cell cell-status">
                    <span class="status-badge" :class="{
                      'status-active': user[7] === 'active',
                      'status-pending': user[7] === 'pending_verification',
                      'status-inactive': user[7] !== 'active' && user[7] !== 'pending_verification'
                    }">
                      {{ user[7] }}
                    </span>
                  </div>

                  <!-- Project Assignment -->
                  <div class="table-cell cell-project">
                    <span v-if="user.assignedProject" class="project-badge">
                      {{ user.assignedProject }}
                    </span>
                    <span v-else class="no-project">No Project</span>
                  </div>

                  <!-- Actions -->
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
        </div>

        <!-- Pending Verification Section (if any) -->
        <div v-if="pendingVerificationEntries.length > 0" class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Pending Verification</h3>
              <span class="entry-count">{{ pendingVerificationEntries.length }} user{{ pendingVerificationEntries.length
                !== 1 ? 's' : '' }} waiting</span>
            </div>
          </div>

          <div class="pending-users">
            <div v-for="user in pendingVerificationEntries" :key="user[0]" class="pending-user-card">
              <div class="pending-user-info">
                <div class="user-avatar" :class="{ 'avatar-initials': user[1] === 'avatar' }"
                     :style="user[1] === 'avatar' ? { backgroundColor: getAvatarColor(user[0]) } : {}">
                  <img v-if="user[1] && user[1] !== 'null' && user[1] !== 'avatar' && user[1] !== 'google'" :src="user[1]" alt="Profile" />
                  <span v-else-if="user[1] === 'avatar'" class="initials">{{ getInitials(user[2], user[3]) }}</span>
                  <ion-icon v-else name="person-outline"></ion-icon>
                </div>
                <div class="user-details">
                  <h4>{{ user[2] }} {{ user[3] }}</h4>
                  <p>{{ user[4] }}</p>
                </div>
              </div>
              <button class="action-btn primary" @click="approve(user[0])">
                <ion-icon name="checkmark-outline"></ion-icon>
                Approve
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create User Modal -->
      <div v-if="showCreateModal" class="custom-modal-overlay" @click="showCreateModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Create New User</h3>
            <button class="modal-close-btn" @click="showCreateModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
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
              <input type="email" v-model="newUser.email_adress" class="modern-input"
                placeholder="Enter email address" />
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
            <div class="form-actions">
              <button class="action-btn secondary" @click="showCreateModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="createUser()">
                Create User
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Assign Project Modal -->
      <div v-if="showAssignModal" class="custom-modal-overlay" @click="showAssignModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Assign Project</h3>
            <button class="modal-close-btn" @click="showAssignModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="assign-user-info">
              <div class="user-avatar" :class="{ 'avatar-initials': selectedUser?.image === 'avatar' }"
                   :style="selectedUser?.image === 'avatar' ? { backgroundColor: getAvatarColor(selectedUser.id) } : {}">
                <img v-if="selectedUser?.image && selectedUser?.image !== 'null' && selectedUser?.image !== 'avatar' && selectedUser?.image !== 'google'" :src="selectedUser.image"
                  alt="Profile" />
                <span v-else-if="selectedUser?.image === 'avatar'" class="initials">{{ getInitialsFromName(selectedUser.name) }}</span>
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

            <div class="form-actions">
              <button class="action-btn secondary" @click="showAssignModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="assignProject()">
                Assign Project
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit User Modal -->
      <div v-if="showEditModal" class="custom-modal-overlay" @click="showEditModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Edit User</h3>
            <button class="modal-close-btn" @click="showEditModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="assign-user-info">
              <div class="user-avatar" :class="{ 'avatar-initials': editUserData.image === 'avatar' }"
                   :style="editUserData.image === 'avatar' ? { backgroundColor: getAvatarColor(editUserData.id) } : {}">
                <img v-if="editUserData.image && editUserData.image !== 'null' && editUserData.image !== 'avatar' && editUserData.image !== 'google'" :src="editUserData.image"
                  alt="Profile" />
                <span v-else-if="editUserData.image === 'avatar'" class="initials">{{ getInitials(editUserData.first_name, editUserData.last_name) }}</span>
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
                <input type="text" v-model="editUserData.first_name" class="modern-input"
                  placeholder="Enter first name" />
              </div>
              <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" v-model="editUserData.last_name" class="modern-input"
                  placeholder="Enter last name" />
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
            <div class="form-actions">
              <button class="action-btn secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="saveUserEdit" :disabled="loading">
                <ion-spinner v-if="loading" name="crescent"></ion-spinner>
                <ion-icon v-else name="save-outline"></ion-icon>
                <span v-if="!loading">Save Changes</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import { defineComponent, ref, getCurrentInstance } from "vue";

export default defineComponent({
  name: "ManageUsers",
  components: {
    SiteTitle,
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
        const response = await axios.post(
          "users.php",
          qs.stringify({ getAllUsers: true }),
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

        // Load user project assignments
        await loadUserAssignments();
      } catch (error) {
        console.error('Error loading users:', error);
      }
    };

    const loadUserAssignments = async function () {
      try {
        const response = await axios.post(
          "users.php",
          qs.stringify({ getUserAssignments: true }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        if (response.data.success) {
          // Add assignment data to users
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
        const response = await axios.post(
          "projects.php",
          qs.stringify({ getAllProjects: true }),
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
        user[2]?.toLowerCase().includes(searchLower) || // first name
        user[3]?.toLowerCase().includes(searchLower) || // last name
        user[4]?.toLowerCase().includes(searchLower) || // email
        user[7]?.toLowerCase().includes(searchLower)    // status
      );
    },
    sortedUsers() {
      if (this.sortColumn === null) {
        return this.filteredUsers;
      }

      const sorted = [...this.filteredUsers].sort((a, b) => {
        let aVal = a[this.sortColumn];
        let bVal = b[this.sortColumn];

        // Special handling for name column (combine first + last)
        if (this.sortColumn === 2) {
          aVal = `${a[2]} ${a[3]}`;
          bVal = `${b[2]} ${b[3]}`;
        }

        // Check if values are numbers
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
          "users.php",
          this.$qs.stringify({
            new_user: "new_user",
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
        await this.$axios.post(
          "users.php",
          this.$qs.stringify({
            updateAccountStatus: "updateAccountStatus",
            userID: userID,
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
          "users.php",
          this.$qs.stringify({
            assignUserProject: "assignUserProject",
            userID: this.selectedUser.id,
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

        // Only include password if it's provided
        if (this.editUserData.password.trim()) {
          updateData.password = this.editUserData.password;
        }

        const response = await this.$axios.post(
          "users.php",
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
        const response = await this.$axios.post(
          "users.php",
          this.$qs.stringify({
            deleteUser: "deleteUser",
            userID: userID
          }),
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
      // Generate consistent colors based on user ID
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
/* Modern Design System - Same as FormDisplay */
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
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  flex-shrink: 0;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
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

.action-btn ion-icon {
  font-size: 16px;
}

/* Search Box */
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
  background: var(--surface);
  color: var(--text-primary);
  min-width: 250px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Stats Grid */
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

/* Modern Table */
.table-wrapper {
  overflow-x: auto;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
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

/* Table Body */
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

/* User Avatar */
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

/* User Info */
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

/* Status Badge */
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

/* Project Badge */
.project-badge {
  display: inline-block;
  padding: 6px 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid rgba(37, 99, 235, 0.2);
}

.no-project {
  color: var(--text-secondary);
  font-style: italic;
  font-size: 12px;
}

/* Action Buttons */
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
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.assign-btn:hover {
  background: rgba(37, 99, 235, 0.2);
  transform: scale(1.05);
}

.edit-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

/* Pending Users */
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
  width: 600px;
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
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  min-height: 0;
}

/* Form Styles */
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

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Success Toast */
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

/* Responsive Design */
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

  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .search-box input {
    min-width: 100%;
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

  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
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

  .custom-modal-header,
  .custom-modal-body {
    padding: 20px;
  }

  .success-toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}
</style>
