<template>
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
                <ion-popover :is-open="openPopover === tr[labels.indexOf('userID')]" @didDismiss="openPopover = null" :event="popoverEvent" side="bottom" alignment="center">
                  <ion-list>
                    <ion-item button @click="changeStatus(tr[labels.indexOf('userID')], 'active'); openPopover = null">Aktivieren</ion-item>
                    <ion-item button @click="changeStatus(tr[labels.indexOf('userID')], 'inactive'); openPopover = null">Deaktivieren</ion-item>
                    <ion-item button @click="changeStatus(tr[labels.indexOf('userID')], 'pending_verification'); openPopover = null">Pending Review</ion-item>
                  </ion-list>
                </ion-popover>
                <ion-chip
                  :color="td === 'active' ? 'success' : (td === 'pending_verification' ? 'warning' : (td === 'inactive' ? 'danger' : 'medium'))"
                  :class="['chip-dropdown',
                    td === 'active' ? 'success' :
                    td === 'pending_verification' ? 'warning' :
                    td === 'inactive' ? 'danger' : 'medium']"
                  @click="onChipClick($event, tr[labels.indexOf('userID')])"
                >
                  {{ String(td).charAt(0).toUpperCase() + String(td).slice(1) }}
                  <ion-icon name="chevron-down-outline" style="margin-left:4px;font-size:14px;vertical-align:middle;" />
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
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: "TableCard",
  props: {
    labels: Array,
    data: Array,
  },
  data() {
    return {
      sortColumn: null,
      sortDirection: 'asc',
      searchQuery: '',
      openPopover: null,
      popoverEvent: null
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
    }
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
    async deactivateUser(userID) {
     // if (!confirm('User wirklich deaktivieren?')) return;
      try {
        await this.$root.$options.appContext.config.globalProperties.$axios.post(
          'users.php',
          this.$root.$options.appContext.config.globalProperties.$qs.stringify({
            deactivateUser: '1',
            userID: userID
          })
        );
        this.$emit('refresh');
      } catch (e) {
        alert('Fehler beim Deaktivieren');
      }
    },
    async changeStatus(userID, newStatus) {
      //if (!confirm(`User wirklich auf ${newStatus} setzen?`)) return;
      try {
        await this.$axios.post(
          'users.php',
          this.$qs.stringify({
            updateAccountStatus: 'updateAccountStatus',
            userID: userID,
            newStatus: newStatus
          })
        );
        this.$emit('refresh');
      } catch (e) {
        alert('Fehler beim Statuswechsel');
      }
    },
    onChipClick(event, userID) {
      this.popoverEvent = event;
      this.openPopover = userID;
    }
  }
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

@media (prefers-color-scheme: dark) {
  .sortable-header:hover {
    background-color: var(--ion-color-step-150, #2223);
  }
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

.chip-dropdown {
  cursor: pointer;
  box-shadow: none !important;
  border: 1px solid var(--ion-color-step-150, #2223);
  background: var(--ion-color-step-50, #2221);
  opacity: 0.85;
  transition: box-shadow 0.2s, border 0.2s, opacity 0.2s;
}
.chip-dropdown.success:hover {
  border: 1.5px solid var(--ion-color-success, #2dd36f);
  opacity: 1;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
}
.chip-dropdown.warning:hover {
  border: 1.5px solid var(--ion-color-warning, #ffc409);
  opacity: 1;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
}
.chip-dropdown.danger:hover {
  border: 1.5px solid var(--ion-color-danger, #eb445a);
  opacity: 1;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
}
.chip-dropdown.medium:hover {
  border: 1.5px solid var(--ion-color-medium, #92949c);
  opacity: 1;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
}

ion-popover {
  --box-shadow: 0 2px 8px 0 rgba(0,0,0,0.10);
  --background: var(--ion-background-color, #222);
  --border-radius: 8px;
  --min-width: 160px;
  --max-width: 220px;
  --padding: 0;
}
ion-popover ion-list {
  background: transparent;
  box-shadow: none;
  border-radius: 8px;
}
ion-popover ion-item {
  font-size: 0.98em;
  background: transparent;
  color: var(--ion-color-medium);
  border-radius: 6px;
  margin: 2px 0;
  transition: background 0.15s;
}
ion-popover ion-item:hover {
  background: var(--ion-color-step-150, #2223);
  color: var(--ion-color-primary, #3880ff);
}
</style>
