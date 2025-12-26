<template>
  <ion-page>
    <ion-content class="modern-content" v-if="token">
      <div class="page-container">
        <div class="profile-header">
          <div class="profile-card">
            <div class="avatar-section">
              <div class="profile-avatar-wrapper">
                <AvatarLarge :profileImg="userData.profileImg" :firstName="userData.firstName"
                  :lastName="userData.lastName" avatarColor="green" />
              </div>
            </div>
            <div class="user-info">
              <h2 class="user-name">{{ userData.firstName || 'Loading...' }} {{ userData.lastName || '' }}</h2>
              <p class="user-email">{{ userData.email || 'Loading email...' }}</p>
              <div class="user-status">
                <span class="status-badge" :class="getStatusClass(userData.accountStatus)">
                  {{ userData.accountStatus || 'Active' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="account-grid">
          <div class="grid-header">
            <h3>Account Management</h3>
          </div>
          <div class="cards-grid">
            <div v-for="card in cards" :key="card.title" class="account-card" @click="navigateToCard(card)">
              <div class="card-icon">
                <ion-icon :name="card.icon"></ion-icon>
              </div>
              <div class="card-content">
                <h4>{{ card.title }}</h4>
                <p>{{ card.description }}</p>
              </div>
              <div class="card-arrow">
                <ion-icon name="chevron-forward-outline"></ion-icon>
              </div>
            </div>
          </div>
          <div class="quick-actions">
            <div class="action-header">
              <h4>Quick Actions</h4>
            </div>
            <div class="actions-grid">
              <button class="action-btn secondary" @click="editProfile">
                <ion-icon name="create-outline"></ion-icon>
                <span>Edit Profile</span>
              </button>
              <button class="action-btn secondary" @click="changePassword">
                <ion-icon name="lock-closed-outline"></ion-icon>
                <span>Change Password</span>
              </button>
              <button class="action-btn danger" @click="logout">
                <ion-icon name="log-out-outline"></ion-icon>
                <span>Log Out</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { getUserData } from "@/userData";
import AvatarLarge from "@/components/AvatarLarge.vue";

export default defineComponent({
  name: "MyAccount",
  data() {
    return {
      token: localStorage.getItem("token"),
      useSystemDefault: true,
      selectedTheme: "light",
      userData: {},
      cards: [
        {
          title: "Personal Information",
          icon: "information-outline",
          description: "Update your personal details and profile information"
        },
        {
          title: "Settings",
          icon: "cog-outline",
          description: "Configure app settings and preferences"
        },
        {
          title: "Preferences",
          icon: "sunny-outline",
          description: "Customize your app theme and display options"
        },
        {
          title: "Account Security",
          icon: "key-outline",
          description: "Manage your password and security settings"
        },
        {
          title: "My Team",
          icon: "people-outline",
          description: "View and manage your team members"
        },
        {
          title: "My Projects",
          icon: "folder-outline",
          description: "Access and organize your projects"
        },
      ],
    };
  },
  components: {
    AvatarLarge,
  },
  methods: {
    getStatusClass(status) {
      const statusLower = (status || 'active').toLowerCase();
      switch (statusLower) {
        case 'active': return 'status-active';
        case 'pending': return 'status-pending';
        case 'suspended': return 'status-suspended';
        default: return 'status-active';
      }
    },
    navigateToCard(card) {
      const route = '/my-account/' + card.title.replaceAll(' ', '-').toLowerCase();
      this.$router.push(route);
    },
    editProfile() {
      this.$router.push('/my-account/personal-information');
    },
    changePassword() {
      this.$router.push('/my-account/account-security');
    },
    logout() {
      this.$router.push('/my-account/logout');
    }
  },
  async mounted() {
    try {
      this.userData = await getUserData();
      console.log('UserData loaded:', this.userData);
    } catch (error) {
      console.error('Error loading user data:', error);
      this.userData = {
        firstName: '',
        lastName: '',
        email: '',
        profileImg: '',
        accountStatus: 'Active'
      };
    }
  },
});
</script>

<style scoped>
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
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.profile-header {
  margin-bottom: 32px;
}

.profile-card {
  display: flex;
  align-items: center;
  gap: 24px;
  background: var(--surface);
  padding: 32px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  min-height: 120px;
  width: 100%;
}

.avatar-section {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-avatar-wrapper {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid var(--border);
  box-shadow: var(--shadow-md);
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-avatar-wrapper :deep(ion-avatar) {
  width: 100% !important;
  height: 100% !important;
}

.profile-avatar {
  width: 120px !important;
  height: 120px !important;
  border: 4px solid var(--border);
  box-shadow: var(--shadow-md);
}

.user-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.user-name {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.user-email {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  font-size: 16px;
}

.user-status {
  display: flex;
  align-items: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: #dcfce7;
  color: var(--success-color);
}

.status-pending {
  background: #fef3c7;
  color: var(--warning-color);
}

.status-suspended {
  background: #fee2e2;
  color: var(--danger-color);
}

.account-grid {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.grid-header {
  padding: 24px 32px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.grid-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.grid-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.cards-grid {
  padding: 24px 32px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.account-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.account-card:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.card-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
}

.card-icon ion-icon {
  font-size: 24px;
}

.card-content {
  flex: 1;
}

.card-content h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.card-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
}

.card-arrow {
  flex-shrink: 0;
  color: var(--text-muted);
  transition: all 0.2s ease;
}

.account-card:hover .card-arrow {
  color: var(--primary-color);
  transform: translateX(4px);
}

.card-arrow ion-icon {
  font-size: 20px;
}

.quick-actions {
  border-top: 1px solid var(--border);
  padding: 24px 32px;
  background: var(--background);
}

.action-header {
  margin-bottom: 16px;
}

.action-header h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.actions-grid {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
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

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border-color: var(--border);
}

.action-btn.secondary:hover {
  background: var(--background);
  border-color: var(--secondary-color);
}

.action-btn.danger {
  background: #fef2f2;
  color: var(--danger-color);
  border-color: #fecaca;
}

.action-btn.danger:hover {
  background: #fee2e2;
  border-color: var(--danger-color);
}

.action-btn ion-icon {
  font-size: 16px;
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

  .status-active {
    background: #065f46;
    color: #10b981;
  }

  .status-pending {
    background: #92400e;
    color: #f59e0b;
  }

  .status-suspended {
    background: #991b1b;
    color: #ef4444;
  }

  .action-btn.danger {
    background: #7f1d1d;
    color: #f87171;
    border-color: #991b1b;
  }

  .action-btn.danger:hover {
    background: #991b1b;
    border-color: #dc2626;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .profile-card {
    flex-direction: column;
    text-align: center;
    padding: 24px;
    gap: 16px;
    align-items: center;
  }

  .profile-avatar-wrapper {
    width: 100px;
    height: 100px;
  }

  .profile-avatar {
    width: 100px !important;
    height: 100px !important;
  }

  .user-name {
    font-size: 24px;
  }

  .grid-header,
  .cards-grid,
  .quick-actions {
    padding: 20px;
  }

  .cards-grid {
    grid-template-columns: 1fr;
  }

  .account-card {
    padding: 16px;
  }

  .actions-grid {
    flex-direction: column;
  }

  .action-btn {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .account-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .card-arrow {
    transform: rotate(90deg);
  }

  .account-card:hover .card-arrow {
    transform: rotate(90deg) translateX(4px);
  }
}
</style>
