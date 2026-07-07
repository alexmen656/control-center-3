<template>
  <ion-page>
    <!--   <ion-header>
        <ion-toolbar>
          <ion-title>Projektübersicht</ion-title>
        </ion-toolbar>
      </ion-header>-->
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <ion-card>
              <h2 class="info-card-heading">Tools</h2>
              <ion-list v-if="tools.length > 0">
                <ion-item v-for="tool in tools" :key="tool.id">
                  <ion-icon v-if="tool.icon" :name="tool.icon" />
                  <ion-label>
                    <h2 @click="goToTool(tool.name)">
                      {{
                        tool.name.charAt(0).toUpperCase() + tool.name.slice(1)
                      }}
                    </h2>
                    <!--  <p>Zugriffsrechte:</p>-->
                  </ion-label>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>No tools yet</h2>
                </ion-label>
              </ion-item>
            </ion-card>
            <ion-card>
              <h2 class="info-card-heading">
                Users
                <div style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                  ">
                  <ion-icon @click="setOpen(true)" name="add" />
                </div>
              </h2>
              <ion-list v-if="users.length > 0">
                <ion-item v-for="user in users" :key="user.id">
                  <!-- <ion-icon v-if="tool.icon" :name="tool.icon" />-->
                  <ion-label>
                    <h2>
                      {{
                        user.name.charAt(0).toUpperCase() + user.name.slice(1)
                      }}
                    </h2>
                    <p v-if="user.role">
                      Role: {{ user.role.name }}
                    </p>
                    <p v-else>
                      Role: Not assigned
                    </p>
                  </ion-label>
                  <ion-button 
                    slot="end" 
                    fill="clear" 
                    @click="editUserRole(user)"
                    v-if="canManageUsers"
                  >
                    <ion-icon name="create-outline" />
                  </ion-button>
                </ion-item>
              </ion-list>
              <ion-item v-else>
                <ion-label>
                  <h2>An error occured</h2>
                </ion-label>
              </ion-item>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
      <!--<ion-list>
          <ion-item v-for="(user, index) in users" :key="index">
            <ion-label>
              <h2>{{ user.name }}</h2>
              <p>Zugriffsrechte: {{ user.access }}</p>
            </ion-label>
          </ion-item>
        </ion-list>-->

      <!-- Modal: Invite User -->
      <ion-modal :is-open="isOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button color="primary" @click="cancel()">Cancel</ion-button>
            </ion-buttons>
            <ion-title style="text-align: center">Invite User</ion-title>
            <ion-buttons slot="end">
              <ion-button color="primary" :strong="true" @click="confirm()">Confirm</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <FloatingInput 
            defaultVal="" 
            label="Email" 
            placeholder="john.doe@control-center.eu" 
            type="email"
            v-model="email" 
          />
          
          <div style="margin-top: 20px;">
            <ion-label style="display: block; margin-bottom: 8px; font-weight: 600;">
              Select Role
            </ion-label>
            <ion-select 
              v-model="selectedRoleId" 
              placeholder="Choose a role"
              interface="action-sheet"
            >
              <ion-select-option 
                v-for="role in availableRoles" 
                :key="role.id" 
                :value="role.id"
              >
                {{ role.name }}
              </ion-select-option>
            </ion-select>
            
            <div 
              v-if="selectedRoleDescription" 
              style="margin-top: 12px; padding: 12px; background: rgba(128,128,128,0.1); border-radius: 8px;"
            >
              <p style="margin: 0; font-size: 0.9em; color: var(--ion-color-medium);">
                {{ selectedRoleDescription }}
              </p>
            </div>
          </div>
        </ion-content>
      </ion-modal>

      <!-- Modal: Edit User Role -->
      <ion-modal :is-open="isEditModalOpen" ref="editModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button color="primary" @click="cancelEdit()">Cancel</ion-button>
            </ion-buttons>
            <ion-title style="text-align: center">Edit User Role</ion-title>
            <ion-buttons slot="end">
              <ion-button color="primary" :strong="true" @click="confirmEdit()">Confirm</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding" v-if="editingUser">
          <div style="margin-bottom: 20px;">
            <h3>{{ editingUser.name }}</h3>
            <p style="color: var(--ion-color-medium);">{{ editingUser.email }}</p>
          </div>

          <div>
            <ion-label style="display: block; margin-bottom: 8px; font-weight: 600;">
              Change Role
            </ion-label>
            <ion-select 
              v-model="editingRoleId" 
              placeholder="Choose a role"
              interface="action-sheet"
            >
              <ion-select-option 
                v-for="role in availableRoles" 
                :key="role.id" 
                :value="role.id"
              >
                {{ role.name }}
              </ion-select-option>
            </ion-select>
            
            <div 
              v-if="editingRoleDescription" 
              style="margin-top: 12px; padding: 12px; background: rgba(128,128,128,0.1); border-radius: 8px;"
            >
              <p style="margin: 0; font-size: 0.9em; color: var(--ion-color-medium);">
                {{ editingRoleDescription }}
              </p>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import FloatingInput from "@/components/FloatingInput.vue";
import { ref } from "vue";

export default {
  name: "ProjectView",
  components: {
    FloatingInput,
  },
  data() {
    return {
      tools: [],
      users: [],
      email: "",
      availableRoles: [],
      selectedRoleId: null,
      editingUser: null,
      editingRoleId: null,
      userPermissions: null,
    };
  },
  setup() {
    const isOpen = ref(false);
    const isEditModalOpen = ref(false);
    
    const setOpen = (open) => {
      isOpen.value = open;
      console.log(1);
    };

    const setEditModalOpen = (open) => {
      isEditModalOpen.value = open;
    };

    return {
      isOpen,
      isEditModalOpen,
      setOpen,
      setEditModalOpen,
    };
  },
  created() {
    // Lade verfügbare Rollen
    this.loadRoles();
    
    // Lade Benutzer-Berechtigungen
    this.loadUserPermissions();
    
    this.$axios
      .get("sidebar.php?getSideBarByProjectName=" + this.$route.params.project)
      .then((response) => {
        this.tools = response.data.tools;
      });
    this.$axios
      .post(
        "projects.php",
        this.$qs.stringify({
          getProjectUsers: "getProjectUsers",
          project: this.$route.params.project,
        })
      )
      .then((response2) => {
        this.users = response2.data;
      });
  },
  computed: {
    selectedRoleDescription() {
      if (!this.selectedRoleId) return null;
      const role = this.availableRoles.find(r => r.id === this.selectedRoleId);
      return role ? role.description : null;
    },
    editingRoleDescription() {
      if (!this.editingRoleId) return null;
      const role = this.availableRoles.find(r => r.id === this.editingRoleId);
      return role ? role.description : null;
    },
    canManageUsers() {
      return this.userPermissions?.project?.manage_users === true;
    }
  },
  methods: {
    async loadRoles() {
      try {
        const response = await this.$axios.post(
          "roles.php",
          this.$qs.stringify({
            getAllRoles: "getAllRoles"
          })
        );
        if (response.data.success) {
          this.availableRoles = response.data.roles;
          // Setze Standardrolle auf "Editor" falls vorhanden
          const editorRole = this.availableRoles.find(r => r.slug === 'editor');
          if (editorRole) {
            this.selectedRoleId = editorRole.id;
          }
        }
      } catch (error) {
        console.error("Failed to load roles:", error);
      }
    },
    async loadUserPermissions() {
      try {
        const response = await this.$axios.post(
          "roles.php",
          this.$qs.stringify({
            getUserRole: "getUserRole",
            project: this.$route.params.project
          })
        );
        if (response.data.success && response.data.role) {
          this.userPermissions = response.data.role.permissions;
        }
      } catch (error) {
        console.error("Failed to load user permissions:", error);
      }
    },
    editUserRole(user) {
      this.editingUser = user;
      this.editingRoleId = user.role ? user.role.id : null;
      this.setEditModalOpen(true);
    },
    cancelEdit() {
      this.editingUser = null;
      this.editingRoleId = null;
      this.setEditModalOpen(false);
    },
    async confirmEdit() {
      if (!this.editingUser || !this.editingRoleId) {
        alert("Please select a role");
        return;
      }

      try {
        const response = await this.$axios.post(
          "roles.php",
          this.$qs.stringify({
            assignRole: "assignRole",
            project: this.$route.params.project,
            targetUserId: this.editingUser.id,
            roleId: this.editingRoleId
          })
        );

        if (response.data.success) {
          alert("Role updated successfully");
          // Reload users
          this.loadUsers();
          this.cancelEdit();
        } else {
          alert("Failed to update role: " + response.data.message);
        }
      } catch (error) {
        console.error("Failed to update role:", error);
        alert("Failed to update role");
      }
    },
    async loadUsers() {
      try {
        const response = await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            getProjectUsers: "getProjectUsers",
            project: this.$route.params.project,
          })
        );
        this.users = response.data;
      } catch (error) {
        console.error("Failed to load users:", error);
      }
    },
    cancel() {
      this.setOpen(false);
      this.email = "";
      this.selectedRoleId = null;
    },
    async confirm() {
      if (!this.email) {
        alert("Please enter an email");
        return;
      }
      
      if (!this.selectedRoleId) {
        alert("Please select a role");
        return;
      }

      this.setOpen(false);

      try {
        const response = await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            addUserToProject: "addUserToProject",
            project: this.$route.params.project,
            email: this.email,
            roleId: this.selectedRoleId,
          })
        );

        if (response.data.success) {
          alert("User invited successfully!");
          // Reload users
          this.loadUsers();
          this.email = "";
          this.selectedRoleId = null;
        } else {
          alert("Failed to invite user: " + response.data.message);
        }
      } catch (error) {
        console.error("Failed to invite user:", error);
        alert("Failed to invite user");
      }
    },
    goToTool(tool) {
      if (tool.toLowerCase().includes("dashboard-")) {
        this.$router.push(
          "/project/" +
          this.$route.params.project +
          "/dashboard/" +
          tool.toLowerCase().replaceAll(" ", "-")
        );
      } else {
        this.$router.push(
          "/project/" +
          this.$route.params.project +
          "/" +
          tool.toLowerCase().replaceAll(" ", "-")
        );
      }
    },
  },
};
</script>
<style scoped>
ion-card {
  border-radius: 28px;
}

ion-icon {
  margin-right: 0.75rem;
}

ion-card:first-of-type,
ion-card:nth-of-type(2) {
  margin-bottom: 1rem;
}

@media (prefers-color-scheme: dark) {

  ion-list,
  ion-item {
    background: #1a1a1a;
    --background: #1a1a1a;
  }

  ion-card {
    background: #2a2a2a;
    border-color: #3a3a3a;
  }

  ion-content {
    --background: #0a0a0a;
  }
}

.info-card-heading {
  padding-left: 0.8rem;
  margin-top: 8px;
  margin-bottom: 4px;
  display: flex;
  justify-content: space-between;
}
</style>
