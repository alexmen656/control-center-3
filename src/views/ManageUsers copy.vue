<template>
  <ion-page>
    <ion-content>
      <AlertMessage
        v-if="successMessage"
        :message="{ title: 'Success!', content: 'User created successful' }"
      />
<ion-card>
    <!-- Search Bar -->
    <div class="search-container">
      <ion-item class="ion-no-padding" lines="none">
        <ion-icon name="search-outline" slot="start"></ion-icon>
        <ion-input
          v-model="searchQuery"
          placeholder="Durchsuchen..."
          clear-input="true"
          @ionInput="searchQuery = $event.target.value"
        ></ion-input>
        <ion-chip v-if="filteredAndSortedData.length !== data.length" color="primary" slot="end" size="small">
          {{ filteredAndSortedData.length }}/{{ data.length }}
        </ion-chip>
      </ion-item>
    </div>

    <div class="table-container">
      <table>
        <tbody>
          <tr>
            <th 
              v-for="(label, index) in labels" 
              :key="label"
              @click="sortBy(index)"
              class="sortable-header"
            >
              {{ label }}
              <span class="sort-indicator">
                <ion-icon 
                  v-if="sortColumn === index && sortDirection === 'asc'" 
                  name="chevron-up-outline"
                ></ion-icon>
                <ion-icon 
                  v-else-if="sortColumn === index && sortDirection === 'desc'" 
                  name="chevron-down-outline"
                ></ion-icon>
                <ion-icon 
                  v-else 
                  name="swap-vertical-outline" 
                  class="sort-default"
                ></ion-icon>
              </span>
            </th>
          </tr>
          <tr v-for="tr in filteredAndSortedData" :key="tr">
            <td v-for="(td, idx) in tr" :key="idx">
              <template v-if="labels[idx] === 'account_status'">
                <ion-chip :color="td === 'active' ? 'success' : (td === 'pending_verification' ? 'warning' : 'medium')">
                  {{ td }}
                </ion-chip>
              </template>
              <template v-else>
                <span v-html="highlightText(td)"></span>
              </template>
            </td>
          </tr>
          <tr v-if="filteredAndSortedData.length === 0 && searchQuery">
            <td :colspan="labels.length" class="no-results">
              <div class="no-results-content">
                <ion-icon name="search-outline"></ion-icon>
                <p>Keine Ergebnisse für "{{ searchQuery }}" gefunden</p>
                <ion-button fill="clear" size="small" @click="clearSearch">
                  Filter zurücksetzen
                </ion-button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </ion-card>
      <h2 v-if="pendingVerificationEntries.length > 0">
        Waiting for verification
      </h2>

      <ion-card v-if="pendingVerificationEntries.length > 0">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th 
                  v-for="(label, index) in verificationLabels" 
                  :key="label"
                  @click="sortVerificationBy(index)"
                  class="sortable-header"
                >
                  {{ label }}
                  <span class="sort-indicator">
                    <ion-icon 
                      v-if="verificationSortColumn === index && verificationSortDirection === 'asc'" 
                      name="chevron-up-outline"
                    ></ion-icon>
                    <ion-icon 
                      v-else-if="verificationSortColumn === index && verificationSortDirection === 'desc'" 
                      name="chevron-down-outline"
                    ></ion-icon>
                    <ion-icon 
                      v-else 
                      name="swap-vertical-outline" 
                      class="sort-default"
                    ></ion-icon>
                  </span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tr in sortedVerificationEntries" :key="tr">
                <td>{{ tr[0] }}</td>
                <td>{{ tr[1] }}</td>
                <td>{{ tr[2] }}</td>
                <td>{{ tr[3] }}</td>
                <td>{{ tr[4] }}</td>
                <td>{{ tr[5] }}</td>
                <td>{{ tr[6] }}</td>
                <td>
                  <ion-chip :color="tr[7] === 'active' ? 'success' : (tr[7] === 'pending_verification' ? 'warning' : 'medium')">
                    {{ tr[7] }}
                  </ion-chip>
                </td>
                <td>
                  <ion-button color="success" size="small" @click="approve(tr[0])"
                    >Approve</ion-button
                  >
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </ion-card>

      <ion-button id="open-modal">
        <ion-icon name="add-outline" />
        New User
      </ion-button>
      <ion-modal ref="modal" trigger="open-modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="cancel()" style="color: red"
                >Cancel</ion-button
              >
            </ion-buttons>
            <ion-title style="text-align: center">Create new user</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="confirm()" style="color: red"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-grid>
            <ion-row>
              <ion-col>
                <ion-item>
                  <ion-label position="stacked">First name</ion-label>
                  <ion-input
                    ref="first_name"
                    type="text"
                    placeholder="Enter your first name"
                    v-model="first_name"
                    :value="first_name"
                    @ionInput="first_name = $event.target.value"
                  />
                </ion-item>
              </ion-col>
              <ion-col>
                <ion-item>
                  <ion-label position="stacked">Last name</ion-label>
                  <ion-input
                    ref="last_name"
                    type="text"
                    placeholder="Enter your last name"
                    v-model="last_name"
                    :value="last_name"
                    @ionInput="last_name = $event.target.value"
                  />
                </ion-item>
              </ion-col>
            </ion-row>
          </ion-grid>
          <ion-item>
            <ion-label position="stacked">Email address</ion-label>
            <ion-input
              ref="email_adress"
              type="email"
              placeholder="Enter your email address"
              v-model="email_adress"
              :value="email_adress"
              @ionInput="email_adress = $event.target.value"
            />
          </ion-item>
          <ion-item>
            <ion-label position="stacked">Password</ion-label>
            <ion-input
              ref="password"
              type="password"
              placeholder="Enter your password"
              v-model="password"
              :value="password"
              @ionInput="password = $event.target.value"
            />
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import AlertMessage from "@/components/AlertMessage.vue";
import { defineComponent, ref, getCurrentInstance } from "vue";

export default defineComponent({
  components: {
    AlertMessage,
  },
  data() {
    return {
      sortColumn: null,
      sortDirection: 'asc',
      searchQuery: '',
      password: "",
      email_adress: "",
      first_name: "",
      last_name: "",
      message: "",
      successMessage: "",
      verificationSortColumn: null,
      verificationSortDirection: 'asc',
    };
  },
  computed: {
    filteredData() {
      if (!this.searchQuery) {
        return this.data;
      }
      
      const query = this.searchQuery.toLowerCase();
      return this.data.filter(row => {
        return row.some(cell => 
          String(cell).toLowerCase().includes(query)
        );
      });
    },
    filteredAndSortedData() {
      let result = this.filteredData;
      
      if (this.sortColumn !== null) {
        result = [...result].sort((a, b) => {
          const aVal = a[this.sortColumn];
          const bVal = b[this.sortColumn];
          
          // Check if values are numbers
          const aNum = parseFloat(aVal);
          const bNum = parseFloat(bVal);
          
          if (!isNaN(aNum) && !isNaN(bNum)) {
            // Numeric sort
            return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
          } else {
            // String sort
            const aStr = String(aVal).toLowerCase();
            const bStr = String(bVal).toLowerCase();
            
            if (this.sortDirection === 'asc') {
              return aStr.localeCompare(bStr);
            } else {
              return bStr.localeCompare(aStr);
            }
          }
        });
      }
      
      return result;
    },
     verificationLabels() {
      return ['User ID', 'Profile Image', 'First Name', 'Last Name', 'E-Mail', 'Password', 'Google LogIn', 'Account Status', 'Approve'];
    },
    sortedVerificationEntries() {
      if (this.verificationSortColumn === null) {
        return this.pendingVerificationEntries;
      }
      
      const sorted = [...this.pendingVerificationEntries].sort((a, b) => {
        const aVal = a[this.verificationSortColumn];
        const bVal = b[this.verificationSortColumn];
        
        // Check if values are numbers
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
          // Numeric sort
          return this.verificationSortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          // String sort
          const aStr = String(aVal).toLowerCase();
          const bStr = String(bVal).toLowerCase();
          
          if (this.verificationSortDirection === 'asc') {
            return aStr.localeCompare(bStr);
          } else {
            return bStr.localeCompare(aStr);
          }
        }
      });
      
      return sorted;
    }
  },
  setup() {
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const labels = ref([]);
    const data = ref([]);
    const data2 = ref({});
    const pendingVerificationEntries = ref([]);

    const loadUsers = async function() {
      axios
        .post(
          "users.php",
          qs.stringify({ getAllUsers: true })
        )
        .then((res) => {
          labels.value = res.data.labels;
          data.value = res.data.data;

          data2.value = res.data;

          const accountStatusIndex =
            data2.value.labels.indexOf("account_status");
          pendingVerificationEntries.value = data2.value.data.filter(
            (entry) => entry[accountStatusIndex] === "pending_verification"
          );
        });
    };

    loadUsers();

    return {
      labels,
      data,
      pendingVerificationEntries,
    };
  },
  methods: {
     sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        // Toggle direction if same column
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        // New column, start with ascending
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }
    },
    clearSearch() {
      this.searchQuery = '';
    },
    highlightText(text) {
      if (!this.searchQuery || !text) {
        return String(text);
      }
      
      const searchTerm = this.searchQuery.trim();
      if (!searchTerm) {
        return String(text);
      }
      
      // Escape special regex characters in search term
      const escapedSearchTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      
      // Create regex for case-insensitive search
      const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
      
      // Replace matches with highlighted version
      return String(text).replace(regex, '<mark class="search-highlight">$1</mark>');
    },
    sortVerificationBy(columnIndex) {
      if (this.verificationSortColumn === columnIndex) {
        // Toggle direction if same column
        this.verificationSortDirection = this.verificationSortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        // New column, start with ascending
        this.verificationSortColumn = columnIndex;
        this.verificationSortDirection = 'asc';
      }
    },
    cancel() {
      this.$refs.modal.$el.dismiss(null, "cancel");
    },
    confirm() {
      if (this.password && this.email_adress && this.first_name) {
        this.$axios
          .post(
            "users.php",
            this.$qs.stringify({
              new_user: "new_user",
              first_name: this.first_name,
              last_name: this.last_name,
              email_adress: this.email_adress,
              password: this.password,
            })
          )
          .then((res) => {
            console.log(res);
            this.successMessage = res.data;

            this.$axios
              .post(
                "mysql.php",
                this.$qs.stringify({ getTableByName: "control_center_users" })
              )
              .then((res) => {
                this.labels = res.data.labels;
                this.data = res.data.data;
              });

            setTimeout(() => {
              this.successMessage = "";
              console.log(this.successMessage);
            }, 3000);
          });
        this.$refs.modal.$el.dismiss(null, "confirm");
      } else {
        console.log("empty");
      }
    },
    approve(userID) {
      this.$axios
        .post(
          "users.php",
          this.$qs.stringify({
            updateAccountStatus: "updateAccountStatus",
            userID: userID,
            newStatus: "active",
          })
        )
        .then(() => {
          this.$axios
            .post(
              "users.php",
              this.$qs.stringify({ getAllUsers: true })
            )
            .then((res) => {
              this.labels = res.data.labels;
              this.data = res.data.data;
              this.data2 = res.data;
              const accountStatusIndex =
                this.data2.labels.indexOf("account_status");
              this.pendingVerificationEntries = this.data2.data.filter(
                (entry) => entry[accountStatusIndex] === "pending_verification"
              );
            });
          alert(userID + " approved");
        });
    },
  },
});
</script>


















<style scoped>

.search-container {
  border-bottom: 1px solid var(--ion-color-light);
}

.search-container ion-item {
  --background: transparent;
  --border-radius: 6px;
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 6px;
  --padding-bottom: 6px;
  --min-height: 32px;
  font-size: 0.9em;
}

.search-container ion-input {
  --padding-start: 0;
  --padding-end: 0;
}

.search-container ion-icon {
  font-size: 16px;
  color: var(--ion-color-medium);
  margin-right: 8px;
}

.search-container ion-chip {
  --color: var(--ion-color-primary);
  font-size: 0.8em;
  height: 22px;
  margin-left: 8px;
}

.table-container {
  overflow-x: auto;
  width: 100%;
}

table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
  min-width: 600px;
}

td,
th {
  border: none;
  text-align: left;
  padding: 8px;
  white-space: nowrap;
}

th {
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.9em;
  color: var(--ion-color-medium);
}

.sortable-header {
  cursor: pointer;
  user-select: none;
  position: relative;
  transition: background-color 0.2s ease;
}

.sortable-header:hover {
  background-color: var(--ion-color-light);
}

.sort-indicator {
  display: inline-flex;
  align-items: center;
  margin-left: 8px;
  font-size: 0.8em;
}

.sort-default {
  opacity: 0.3;
}

.sortable-header:hover .sort-default {
  opacity: 0.6;
}

.search-highlight {
  background-color: #ffeb3b;
  color: #000;
  padding: 2px 4px;
  border-radius: 3px;
  font-weight: 500;
}

@media (prefers-color-scheme: dark) {
  .search-highlight {
    background-color: #ffc107;
    color: #000;
  }
}

.no-results {
  text-align: center;
  padding: 32px 16px;
}

.no-results-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: var(--ion-color-medium);
}

.no-results-content ion-icon {
  font-size: 48px;
  opacity: 0.5;
}

.no-results-content p {
  margin: 0;
  font-size: 1.1em;
}

tr:nth-child(even) {
  background-color: #e9e9e9;
}

@media (prefers-color-scheme: dark) {
  tr:nth-child(even) {
    background-color: #121212;
  }
  
  .search-container {
    border-bottom-color: var(--ion-color-dark);
  }
}
</style>
