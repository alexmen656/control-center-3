<template>
  <div class="service-status">
    <div v-if="!hideTitle" class="status-header">
      <div>
        <ion-icon name="pulse" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
        {{ title }}
      </div>
      <div class="status-filters">
        <ion-select v-model="selectedDays" placeholder="Zeitraum" interface="popover" @ionChange="fetchStatusHistory">
          <ion-select-option value="1">24 Stunden</ion-select-option>
          <ion-select-option value="3">3 Tage</ion-select-option>
          <ion-select-option value="7">7 Tage</ion-select-option>
          <ion-select-option value="14">14 Tage</ion-select-option>
          <ion-select-option value="30">30 Tage</ion-select-option>
        </ion-select>
        <ion-button size="small" @click="fetchStatusHistory">
          <ion-icon slot="icon-only" name="refresh"></ion-icon>
        </ion-button>
      </div>
    </div>

    <div class="current-status" v-if="lastPing">
      <ion-chip :color="currentStatus === 'up' ? 'success' : 'danger'" class="status-chip">
        <ion-icon :name="currentStatus === 'up' ? 'checkmark-circle' : 'alert-circle'"></ion-icon>
        <ion-label>{{ currentStatus === 'up' ? 'Online' : 'Offline' }}</ion-label>
      </ion-chip>
      <div class="last-ping">
        Letzter Ping: {{ formatDateFull(lastPing) }}
      </div>
    </div>
    <div class="current-status" v-else>
      <ion-chip color="medium" class="status-chip">
        <ion-icon name="help-circle"></ion-icon>
        <ion-label>Unbekannt</ion-label>
      </ion-chip>
      <div class="last-ping">
        Kein Ping aufgezeichnet
      </div>
    </div>

    <div class="status-timeline" v-if="!loading && statusBlocks.length > 0">
      <div class="timeline-header">
        <div class="timeline-title">Status-Verlauf (pro halbe Stunde)</div>
        <div class="timeline-labels">
          <div class="time-label" v-for="(label, index) in timeLabels" :key="index">
            {{ label }}
          </div>
        </div>
      </div>
      
      <div class="timeline-blocks">
        <div class="timeline-day" v-for="(day, dayIndex) in groupedBlocks" :key="dayIndex">
          <div class="day-label">{{ day.date }}</div>
          <div class="day-blocks">
            <div
              v-for="(block, blockIndex) in day.blocks"
              :key="blockIndex"
              :class="{
                'status-block': true,
                'status-up': block.status === 'up' && !block.isCurrentBlock,
                'status-down': block.status === 'down' && !block.isCurrentBlock,
                'status-unknown': block.status === 'unknown' && !block.isCurrentBlock,
                'status-current-up': block.isCurrentBlock && block.status === 'up',
                'status-current-down': block.isCurrentBlock && (block.status === 'down' || block.status === 'unknown')
              }"
              :title="block.tooltip"
            ></div>
          </div>
        </div>
      </div>

      <div class="timeline-footer">
        <div class="status-legend">
          <div class="legend-item">
            <div class="legend-color status-up"></div>
            <div class="legend-label">Online</div>
          </div>
          <div class="legend-item">
            <div class="legend-color status-down"></div>
            <div class="legend-label">Offline</div>
          </div>
          <div class="legend-item">
            <div class="legend-color status-unknown"></div>
            <div class="legend-label">Keine Daten</div>
          </div>
          <div class="legend-item">
            <div class="legend-color status-current-up"></div>
            <div class="legend-label">Aktueller Block (Online)</div>
          </div>
          <div class="legend-item">
            <div class="legend-color status-current-down"></div>
            <div class="legend-label">Aktueller Block (Offline)</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="!loading" class="empty-status">
      <ion-icon name="pulse-outline" size="large"></ion-icon>
      <p>Keine Status-Daten verfügbar</p>
    </div>

    <div v-if="loading" class="loading-status">
      <ion-spinner name="circular"></ion-spinner>
      <p>Lade Status-Daten...</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ServiceStatusHistory',
  props: {
    title: {
      type: String,
      default: 'Service Status'
    },
    projectId: {
      type: String,
      required: true
    },
    service: {
      type: String,
      required: true
    },
    hideTitle: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      history: [],
      loading: false,
      selectedDays: '7', // Default to 7 days
      currentStatus: 'unknown',
      lastPing: null,
      statusBlocks: [],
      timeLabels: [],
      blocksPerDay: 48 // 48 half-hour blocks in a day
    };
  },
  computed: {
    groupedBlocks() {
      const days = {};
      
      for (const block of this.statusBlocks) {
        if (!days[block.day]) {
          days[block.day] = {
            date: block.dayFormatted,
            day: block.day, // YYYY-MM-DD ISO format for sorting
            blocks: []
          };
        }
        days[block.day].blocks.push(block);
      }
      
      // Sort by date, oldest first using the ISO date string
      return Object.values(days).sort((a, b) => {
        return new Date(a.day) - new Date(b.day);
      });
    }
  },
  watch: {
    projectId() {
      this.fetchStatusHistory();
    },
    service() {
      this.fetchStatusHistory();
    }
  },
  mounted() {
    this.generateTimeLabels();
    this.fetchStatusHistory();
  },
  methods: {
    generateTimeLabels() {
      const labels = [];
      // Generate time labels for every 3 hours (00:00, 03:00, etc.)
      for (let hour = 0; hour < 24; hour += 3) {
        const formattedHour = hour.toString().padStart(2, '0');
        labels.push(`${formattedHour}:00`);
      }
      this.timeLabels = labels;
    },
    fetchStatusHistory() {
      if (!this.projectId || !this.service) return;
      
      this.loading = true;
      const params = {
        action: 'history',
        project_id: this.projectId,
        service: this.service,
        days: this.selectedDays
      };
      
      // Convert params to query string
      const queryParams = new URLSearchParams();
      for (const key in params) {
        queryParams.append(key, params[key]);
      }
      
      this.$axios.get(`api/service_status.php?${queryParams.toString()}`, {
        headers: {
          'Authorization': localStorage.getItem('token')
        }
      })
      .then(response => {
        if (response.data.success && response.data.data) {
          this.history = response.data.data.history || [];
          this.currentStatus = response.data.data.current_status || 'unknown';
          this.lastPing = response.data.data.last_ping || null;
          
          // Get the first status entry from history to determine start of monitoring
          let monitoringStartDate = null;
          if (this.history.length > 0) {
            // Find the earliest start_time from all entries
            const earliestEntry = this.history.reduce((earliest, entry) => {
              const entryDate = new Date(entry.start_time);
              return earliest === null || entryDate < earliest ? entryDate : earliest;
            }, null);
            
            monitoringStartDate = earliestEntry;
          }
          
          this.processStatusHistory(monitoringStartDate);
        }
        this.loading = false;
      })
      .catch(error => {
        console.error('Error fetching status history:', error);
        this.loading = false;
      });
    },
    
    // Add a utility method to format dates for MySQL compatibility
    formatDateForMySQL(date) {
      if (!date) return '';
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    
    processStatusHistory(monitoringStartDate) {
      const days = parseInt(this.selectedDays);
      const endDate = new Date();
      const startDate = new Date();
      startDate.setDate(endDate.getDate() - days);
      
      // Set times to beginning of day for start date
      startDate.setHours(0, 0, 0, 0);
      
      console.log('Processing status history:');
      console.log('- Start date:', startDate);
      console.log('- End date:', endDate);
      console.log('- Monitoring start date:', monitoringStartDate);
      
      // Create a map of all half-hour blocks in the date range, with full days
      const blocks = [];
      
      // Get an array of all days in the date range
      const dayArray = [];
      const tempDate = new Date(startDate);
      while (tempDate <= endDate) {
        dayArray.push(new Date(tempDate));
        tempDate.setDate(tempDate.getDate() + 1);
      }
      
      // For each day, create all 48 half-hour blocks
      for (const dayDate of dayArray) {
        const day = dayDate.toISOString().split('T')[0]; // YYYY-MM-DD
        const dayFormatted = this.formatDate(dayDate);
        const isToday = dayDate.toDateString() === endDate.toDateString();
        
        // For each hour of the day
        for (let hour = 0; hour < 24; hour++) {
          // For each half-hour (0 and 30 minutes)
          for (let halfHour = 0; halfHour < 2; halfHour++) {
            const minute = halfHour * 30;
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            
            const startTime = new Date(dayDate);
            startTime.setHours(hour, minute, 0, 0);
            
            const endTime = new Date(startTime);
            endTime.setMinutes(endTime.getMinutes() + 30);
            
            // Modify the skip logic: Only skip future blocks that haven't started yet
            // This ensures that the current block is shown (the block where current time falls within start and end)
            if (isToday && startTime > endDate) {
              continue;
            }
            
            // Check if this is the current block (in progress)
            const now = new Date();
            const isCurrentBlock = startTime <= now && now < endTime;
            
            // Default status is "unknown" (gray)
            let status = 'unknown';
            let tooltip = `${dayFormatted} ${time} - Status: Keine Daten`;
            
            // If we have a valid last ping and the block is not in the future,
            // it's considered monitored after that time
            if (monitoringStartDate && startTime >= monitoringStartDate) {
              status = 'down';
              tooltip = `${dayFormatted} ${time} - Status: Offline`;
            }
            
            blocks.push({
              day,
              dayFormatted,
              time,
              status,
              startTime,
              endTime,
              isToday,
              isCurrentBlock, // Add the new flag
              tooltip
            });
          }
        }
      }
      
      // Now we check the logs to mark blocks as 'up' where we have activity
      // Using the is_ping parameter to specifically get only ping logs, but without restricting the message
      const params = {
        project_id: this.projectId,
        service: this.service,
        limit: 1000, // We need a high limit to get enough logs for the time period
        start_date: this.formatDateForMySQL(startDate), // Use MySQL format
        end_date: this.formatDateForMySQL(endDate),     // Use MySQL format
        is_ping: true // Only get ping logs, but don't restrict the message
      };
      
      // Convert params to query string
      const queryParams = new URLSearchParams();
      for (const key in params) {
        queryParams.append(key, params[key]);
      }
      
      console.log('Fetching logs with params:', params);
      
      // When we have actual log data, update block statuses with 'up' periods
      this.$axios.get(`api/service_logs.php?${queryParams.toString()}`, {
        headers: {
          'Authorization': localStorage.getItem('token')
        }
      })
      .then(response => {
        console.log('Log response:', response.data);
        
        if (response.data.success && response.data.data) {
          const logs = response.data.data;
          console.log(`Found ${logs.length} ping logs`);
          
          // Process logs to mark up time periods
          for (const log of logs) {
            const logTime = new Date(log.timestamp);
            console.log(`Processing ping log at ${logTime}: ${log.message}`);
            
            // For each log, mark the corresponding block as 'up'
            for (const block of blocks) {
              if (logTime >= block.startTime && logTime < block.endTime) {
                console.log(`Log matches block ${block.dayFormatted} ${block.time}`);
                // Only update if the block is after monitoring started (not "unknown")
                if (block.status !== 'unknown') {
                  block.status = 'up';
                  block.tooltip = `${block.dayFormatted} ${block.time} - Status: Online (Log: ${log.message})`;
                }
              }
            }
          }
        }
        
        this.statusBlocks = blocks;
        
        // Log summary of blocks by status for debugging
        const statusCount = {
          up: 0,
          down: 0,
          unknown: 0
        };
        
        blocks.forEach(block => {
          statusCount[block.status]++;
        });
        
        console.log('Status block summary:', statusCount);
      })
      .catch(error => {
        console.error('Error fetching logs:', error);
        this.statusBlocks = blocks;
      });
    },
    formatDate(date) {
      if (!date) return '';
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    formatDateFull(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('de-DE', { 
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
.service-status {
  width: 100%;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.status-filters {
  display: flex;
  align-items: center;
  gap: 10px;
}

.current-status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding: 0.5rem 1rem;
  background-color: rgba(var(--ion-color-medium-rgb), 0.1);
  border-radius: 8px;
}

.status-chip {
  padding: 0.5rem;
}

.last-ping {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.status-timeline {
  margin-top: 1.5rem;
  border: 1px solid rgba(var(--ion-color-medium-rgb), 0.2);
  border-radius: 8px;
  padding: 1rem;
}

.timeline-header {
  margin-bottom: 1rem;
}

.timeline-title {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.timeline-labels {
  display: flex;
  justify-content: space-between;
  margin-left: 100px;  /* Align with the blocks */
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.timeline-blocks {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.timeline-day {
  display: flex;
  align-items: center;
  gap: 10px;
}

.day-label {
  width: 100px;
  text-align: right;
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.day-blocks {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(48, 1fr); /* 48 half-hour blocks per day */
  gap: 1px;
}

.status-block {
  height: 24px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.status-up {
  background-color: var(--ion-color-success);
}

.status-down {
  background-color: var(--ion-color-danger);
}

.status-unknown {
  background-color: var(--ion-color-medium-tint);
}

/* Stil für den aktuellen Block mit Ping (online) */
.status-current-up {
  background-color: #4caf50; /* Kräftiges Grün */
  border: 2px solid #2e7d32; /* Dunklerer Rahmen */
  animation: pulse-green 2s infinite;
  position: relative;
  z-index: 1;
}

/* Stil für den aktuellen Block ohne Ping (offline) */
.status-current-down {
  background-color: #f44336; /* Kräftiges Rot */
  border: 2px solid #c62828; /* Dunklerer Rahmen */
  animation: pulse-red 2s infinite;
  position: relative;
  z-index: 1;
}

@keyframes pulse-green {
  0% {
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); /* Grünliche Pulsation */
  }
  70% {
    box-shadow: 0 0 0 5px rgba(76, 175, 80, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
  }
}

@keyframes pulse-red {
  0% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7); /* Rötliche Pulsation */
  }
  70% {
    box-shadow: 0 0 0 5px rgba(244, 67, 54, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0);
  }
}

.timeline-footer {
  margin-top: 1.5rem;
}

.status-legend {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.legend-color {
  width: 16px;
  height: 16px;
}

.legend-label {
  font-size: 0.8rem;
}

.empty-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  color: var(--ion-color-medium);
}

.loading-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

@media (max-width: 768px) {
  .day-blocks {
    grid-template-columns: repeat(24, 1fr); /* 24 hour blocks for mobile */
  }
  
  .timeline-labels {
    font-size: 0.7rem;
  }
  
  .day-label {
    width: 80px;
    font-size: 0.7rem;
  }
}
</style>