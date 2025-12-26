<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-left">
            <button class="back-btn" @click="$router.go(-1)">
              <ion-icon name="arrow-back-outline"></ion-icon>
            </button>
            <div>
              <h1>Personal Information</h1>
              <p>Update your personal details and profile information</p>
            </div>
          </div>
        </div>

        <!-- Profile Section -->
        <div class="profile-section">
          <div class="profile-card">
            <div class="profile-image-section">
              <div class="avatar-container" @click="takePhoto()">
                <ion-avatar class="profile-avatar">
                  <img v-if="user.profileImg && user.profileImg !== 'avater'" :src="user.profileImg" />
                  <ion-icon v-else name="person-outline" class="placeholder-icon"></ion-icon>
                </ion-avatar>
                <div class="avatar-overlay">
                  <ion-icon name="camera-outline"></ion-icon>
                  <span>Change Photo</span>
                </div>
              </div>
            </div>

            <div class="profile-form">
              <h3>Basic Information</h3>

              <div class="form-grid">
                <!-- First Name -->
                <div class="form-group">
                  <label class="form-label">First Name</label>
                  <div class="input-group">
                    <input v-model="user.firstName.value" :disabled="!user.firstName.editable" class="modern-input"
                      type="text" placeholder="Enter your first name" />
                    <button v-if="!user.firstName.editable" @click="edit('firstName')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                </div>

                <!-- Last Name -->
                <div class="form-group">
                  <label class="form-label">Last Name</label>
                  <div class="input-group">
                    <input v-model="user.lastName.value" :disabled="!user.lastName.editable" class="modern-input"
                      type="text" placeholder="Enter your last name" />
                    <button v-if="!user.lastName.editable" @click="edit('lastName')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                </div>

                <!-- Email -->
                <div class="form-group full-width">
                  <label class="form-label">Email Address</label>
                  <div class="input-group">
                    <input v-model="user.email.value" :disabled="!user.email.editable" class="modern-input" type="email"
                      placeholder="Enter your email" />
                    <button v-if="!user.email.editable" @click="edit('email')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                  <small class="field-note">Email changes require verification</small>
                </div>

                <!-- Phone -->
                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <div class="input-group">
                    <input v-model="user.phone.value" :disabled="!user.phone.editable" class="modern-input" type="tel"
                      placeholder="Enter your phone number" />
                    <button v-if="!user.phone.editable" @click="edit('phone')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                </div>

                <!-- Birthday -->
                <div class="form-group">
                  <label class="form-label">Birthday</label>
                  <div class="input-group">
                    <input v-model="user.birthday.value" :disabled="!user.birthday.editable" class="modern-input"
                      type="date" />
                    <button v-if="!user.birthday.editable" @click="edit('birthday')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                </div>

                <!-- Address -->
                <div class="form-group full-width">
                  <label class="form-label">Address</label>
                  <div class="input-group">
                    <textarea v-model="user.address.value" :disabled="!user.address.editable" class="modern-textarea"
                      rows="3" placeholder="Enter your address"></textarea>
                    <button v-if="!user.address.editable" @click="edit('address')" class="edit-btn">
                      <ion-icon name="pencil-outline"></ion-icon>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="form-actions">
                <button class="action-btn secondary" @click="cancel">
                  <ion-icon name="close-outline"></ion-icon>
                  <span>Cancel</span>
                </button>
                <button class="action-btn primary" @click="save">
                  <ion-icon name="checkmark-outline"></ion-icon>
                  <span>Save Changes</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { usePhotoGallery } from "@/composables/updateProfileImage";//, Photo
import { getUserData } from "@/userData";

export default defineComponent({
  name: "PersonalInformation",
  data() {
    return {
      user: {
        firstName: { value: "", editable: false },
        lastName: { value: "", editable: false },
        email: { value: "", editable: false },
        phone: { value: "", editable: false },
        birthday: { value: "", editable: false },
        address: { value: "", editable: false },
        profileImg: ""
      },
    };
  },
  methods: {
    edit(field) {
      this.user[field].editable = true;
    },
    cancel() {
      // Reset all fields to non-editable
      for (const field in this.user) {
        if (Object.prototype.propertyIsEnumerable.call(this.user, field)) {
          if (this.user[field] && typeof this.user[field] === 'object' && 'editable' in this.user[field]) {
            this.user[field].editable = false;
          }
        }
      }
      // Optionally reload data to reset any unsaved changes
      this.loadUserData();
    },
    async loadUserData() {
      const data = await getUserData();
      if (data) {
        this.user.email.value = data.email || "";
        this.user.firstName.value = data.firstName || "";
        this.user.lastName.value = data.lastName || "";
        this.user.profileImg = data.profileImg || "";
        // Initialize other fields if they exist in userData
        this.user.phone.value = data.phone || "";
        this.user.birthday.value = data.birthday || "";
        this.user.address.value = data.address || "";
      }
    },
    save() {
      const filteredUser = {};
      for (const field in this.user) {
        if (Object.prototype.propertyIsEnumerable.call(this.user, field)) {
          if (field !== "id" && field !== "editable" && field !== "profileImg") {
            if (this.user[field] && typeof this.user[field] === 'object' && 'value' in this.user[field]) {
              filteredUser[field] = this.user[field]["value"];
            }
          }
        }
      }
      filteredUser["editData"] = "editData";

      this.$axios
        .post("user.php?" + this.$qs.stringify(filteredUser), {
          headers: {
            "Content-Type": "application/json",
          },
        })
        .then((response) => {
          console.log(response);
          console.log("API response:", response.data);
          // Reset editable states on successful save
          for (const field in this.user) {
            if (Object.prototype.propertyIsEnumerable.call(this.user, field)) {
              if (this.user[field] && typeof this.user[field] === 'object' && 'editable' in this.user[field]) {
                this.user[field].editable = false;
              }
            }
          }
        })
        .catch((error) => {
          console.error("API error:", error);
        });
    },
  },
  async mounted() {
    try {
      await this.loadUserData();
    } catch (error) {
      console.error('Error loading user data:', error);
      // Set default values if loading fails
      this.user.firstName.value = '';
      this.user.lastName.value = '';
      this.user.email.value = '';
      this.user.profileImg = '';
    }
  },
  setup() {
    const { takePhoto, photos } = usePhotoGallery();
    return {
      takePhoto,
      photos,
    };
  },
});
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
}

.page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.back-btn:hover {
  background: var(--background);
  color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.back-btn ion-icon {
  font-size: 20px;
}

.header-left h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-left p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Profile Section */
.profile-section {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.profile-card {
  display: flex;
  gap: 32px;
  padding: 32px;
}

.profile-image-section {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.avatar-container {
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.avatar-container:hover {
  transform: scale(1.02);
}

.profile-avatar {
  width: 120px !important;
  height: 120px !important;
  border: 4px solid var(--border);
  box-shadow: var(--shadow-md);
  transition: all 0.3s ease;
}

.placeholder-icon {
  font-size: 48px;
  color: var(--text-muted);
}

.avatar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  opacity: 0;
  transition: all 0.3s ease;
  border-radius: 50%;
}

.avatar-container:hover .avatar-overlay {
  opacity: 1;
}

.avatar-overlay ion-icon {
  font-size: 24px;
}

.avatar-overlay span {
  font-size: 12px;
  font-weight: 500;
}

/* Profile Form */
.profile-form {
  flex: 1;
}

.profile-form h3 {
  margin: 0 0 24px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 32px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.input-group {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modern-input,
.modern-textarea {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  font-family: inherit;
}

.modern-input:focus,
.modern-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-input:disabled,
.modern-textarea:disabled {
  background: var(--background);
  color: var(--text-muted);
  cursor: not-allowed;
}

.modern-textarea {
  resize: vertical;
  min-height: 80px;
}

.edit-btn {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--radius);
  background: var(--background);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.edit-btn:hover {
  background: var(--primary-color);
  color: white;
  transform: scale(1.05);
}

.edit-btn ion-icon {
  font-size: 16px;
}

.field-note {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border: 1px solid var(--primary-color);
  box-shadow: var(--shadow);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn.secondary:hover {
  background: var(--background);
  border-color: var(--secondary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
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
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .profile-card {
    flex-direction: column;
    align-items: center;
    padding: 24px;
    gap: 24px;
  }

  .profile-image-section {
    order: -1;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .action-btn {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .header-left h1 {
    font-size: 24px;
  }

  .profile-avatar {
    width: 100px !important;
    height: 100px !important;
  }
}
</style>
