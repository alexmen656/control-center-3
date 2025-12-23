<template>
  <ion-page>
    <ion-content class="modern-content">

      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn primary" @click="toggleCampaignForm">
              <ion-icon name="add-outline"></ion-icon>
              <span>Neue Kampagne</span>
            </button>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn secondary" @click="exportCampaigns()">
              <ion-icon name="download-outline"></ion-icon>
              <span>Export CSV</span>
            </button>
            <button class="action-btn secondary" @click="refreshCampaigns()">
              <ion-icon name="refresh-outline"></ion-icon>
              <span>Aktualisieren</span>
            </button>
            <div class="dropdown">
              <button class="action-btn secondary dropdown-toggle" @click="toggleDropdown">
                <ion-icon name="ellipsis-vertical-outline"></ion-icon>
              </button>
              <div class="dropdown-menu" :class="{ active: dropdownOpen }">
                <a @click="openAnalyticsModal()" class="dropdown-item">
                  <ion-icon name="analytics-outline"></ion-icon>
                  Analytics Dashboard
                </a>
                <a @click="openTemplatesModal()" class="dropdown-item">
                  <ion-icon name="library-outline"></ion-icon>
                  Kampagnen Vorlagen
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-icon">
              <ion-icon name="campaigns-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ totalCampaigns }}</div>
              <div class="kpi-label">Gesamt Kampagnen</div>
              <div class="kpi-trend positive">
                <ion-icon name="trending-up"></ion-icon>
                +{{ Math.abs(campaignGrowth) }}%
              </div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon active">
              <ion-icon name="play-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ activeCampaigns }}</div>
              <div class="kpi-label">Aktive Kampagnen</div>
              <div class="kpi-subtitle">{{ upcomingCampaigns }} geplant</div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon success">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ formatCurrency(totalBudget) }}</div>
              <div class="kpi-label">Gesamt Budget</div>
              <div class="kpi-subtitle">{{ formatCurrency(spentBudget) }} ausgegeben</div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon warning">
              <ion-icon name="eye-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ formatNumber(totalImpressions) }}</div>
              <div class="kpi-label">Impressionen</div>
              <div class="kpi-trend positive">
                <ion-icon name="trending-up"></ion-icon>
                +{{ Math.abs(impressionGrowth) }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="filter-bar">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input 
              v-model="searchTerm"
              @input="filterCampaigns"
              placeholder="Kampagnen durchsuchen..."
              class="search-input"
            />
          </div>
          
          <div class="filter-controls">
            <select v-model="statusFilter" @change="filterCampaigns" class="modern-select">
              <option value="">Alle Status</option>
              <option value="draft">Entwurf</option>
              <option value="scheduled">Geplant</option>
              <option value="active">Aktiv</option>
              <option value="paused">Pausiert</option>
              <option value="completed">Abgeschlossen</option>
            </select>
            
            <select v-model="channelFilter" @change="filterCampaigns" class="modern-select">
              <option value="">Alle Kanäle</option>
              <option value="email">E-Mail</option>
              <option value="social">Social Media</option>
              <option value="ppc">PPC Werbung</option>
              <option value="display">Display Ads</option>
              <option value="content">Content Marketing</option>
            </select>
          </div>
        </div>

        <!-- Campaigns Data Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Kampagnen Übersicht</h3>
              <span class="entry-count">{{ filteredCampaigns.length }} von {{ campaigns.length }} Kampagnen</span>
            </div>
          </div>

          <div v-if="loading" class="loading-container">
            <ion-spinner></ion-spinner>
            <p>Lade Kampagnen...</p>
          </div>

          <div v-else-if="error" class="error-container">
            <ion-icon name="warning"></ion-icon>
            <p>Fehler: {{ error }}</p>
          </div>

          <div v-else class="table-wrapper">
            <div class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="header-cell" @click="sortBy('name')">
                  <span class="header-text">Kampagne</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('name')" :class="getSortClass('name')"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('status')">
                  <span class="header-text">Status</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('status')" :class="getSortClass('status')"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('channel')">
                  <span class="header-text">Kanal</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('channel')" :class="getSortClass('channel')"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('start_date')">
                  <span class="header-text">Startdatum</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('start_date')" :class="getSortClass('start_date')"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('budget')">
                  <span class="header-text">Budget</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('budget')" :class="getSortClass('budget')"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('performance')">
                  <span class="header-text">Performance</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon('performance')" :class="getSortClass('performance')"></ion-icon>
                  </div>
                </div>
                <div class="actions-header">Aktionen</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <!-- No Data State -->
                <div v-if="!filteredCampaigns || filteredCampaigns.length === 0" class="no-data-state">
                  <div class="no-data-content">
                    <ion-icon name="megaphone-outline" class="no-data-icon"></ion-icon>
                    <h4>Keine Kampagnen gefunden</h4>
                    <p>Erstellen Sie Ihre erste Marketing-Kampagne, um zu beginnen.</p>
                    <button class="action-btn primary" @click="toggleCampaignForm">
                      <ion-icon name="add-outline"></ion-icon>
                      Erste Kampagne erstellen
                    </button>
                  </div>
                </div>
                
                <!-- Campaign Rows -->
                <div 
                  v-for="campaign in paginatedCampaigns" 
                  :key="campaign.id" 
                  class="table-row"
                  :class="{ 'row-hover': true }">
                  
                  <div class="table-cell">
                    <div class="campaign-info">
                      <div class="campaign-name">{{ campaign.name }}</div>
                      <div class="campaign-description">{{ campaign.description }}</div>
                    </div>
                  </div>
                  
                  <div class="table-cell">
                    <span class="status-badge" :class="campaign.status">
                      {{ getStatusText(campaign.status) }}
                    </span>
                  </div>
                  
                  <div class="table-cell">
                    <div class="channel-badge" :class="campaign.channel">
                      <ion-icon :name="getChannelIcon(campaign.channel)"></ion-icon>
                      {{ getChannelText(campaign.channel) }}
                    </div>
                  </div>
                  
                  <div class="table-cell">
                    <div class="date-info">
                      <div class="date-primary">{{ formatDate(campaign.start_date) }}</div>
                      <div v-if="campaign.end_date" class="date-secondary">
                        bis {{ formatDate(campaign.end_date) }}
                      </div>
                    </div>
                  </div>
                  
                  <div class="table-cell">
                    <div class="budget-info">
                      <div class="budget-total">{{ formatCurrency(campaign.budget) }}</div>
                      <div class="budget-spent">{{ formatCurrency(campaign.spent) }} ausgegeben</div>
                    </div>
                  </div>
                  
                  <div class="table-cell">
                    <div class="performance-metrics">
                      <div class="metric">
                        <span class="metric-value">{{ formatNumber(campaign.impressions) }}</span>
                        <span class="metric-label">Impressions</span>
                      </div>
                      <div class="metric">
                        <span class="metric-value">{{ campaign.click_rate }}%</span>
                        <span class="metric-label">CTR</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="editCampaign(campaign)" title="Bearbeiten">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn view-btn" @click="viewCampaignDetails(campaign)" title="Details">
                        <ion-icon name="eye-outline"></ion-icon>
                      </button>
                      <button 
                        v-if="campaign.status === 'active'" 
                        class="icon-btn pause-btn" 
                        @click="pauseCampaign(campaign.id)" 
                        title="Pausieren">
                        <ion-icon name="pause-outline"></ion-icon>
                      </button>
                      <button 
                        v-else-if="campaign.status === 'paused'" 
                        class="icon-btn play-btn" 
                        @click="resumeCampaign(campaign.id)" 
                        title="Fortsetzen">
                        <ion-icon name="play-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteCampaign(campaign.id)" title="Löschen">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="pagination" v-if="totalPages > 1">
            <button 
              class="action-btn secondary" 
              :disabled="currentPage === 1" 
              @click="currentPage--">
              <ion-icon name="chevron-back"></ion-icon>
            </button>
            <span>Seite {{ currentPage }} von {{ totalPages }}</span>
            <button 
              class="action-btn secondary" 
              :disabled="currentPage === totalPages" 
              @click="currentPage++">
              <ion-icon name="chevron-forward"></ion-icon>
            </button>
          </div>
        </div>

        <!-- Campaign Form Section -->
        <div class="form-section" :class="{ 'form-visible': showCampaignForm }">
          <div class="form-card">
            <div class="form-header">
              <h3>{{ editingCampaign ? 'Kampagne bearbeiten' : 'Neue Kampagne' }}</h3>
              <button class="close-form-btn" @click="closeCampaignForm">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <div class="form-content">
              <form @submit.prevent="saveCampaign" class="campaign-form">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Kampagnen Name</label>
                    <input 
                      v-model="campaignForm.name" 
                      type="text" 
                      class="modern-input" 
                      required 
                      placeholder="Kampagnen Name eingeben..."
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Status</label>
                    <select v-model="campaignForm.status" class="modern-select" required>
                      <option value="draft">Entwurf</option>
                      <option value="scheduled">Geplant</option>
                      <option value="active">Aktiv</option>
                      <option value="paused">Pausiert</option>
                      <option value="completed">Abgeschlossen</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Beschreibung</label>
                  <textarea 
                    v-model="campaignForm.description" 
                    class="modern-textarea" 
                    rows="3"
                    placeholder="Kurze Beschreibung der Kampagne..."
                  ></textarea>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Marketing Kanal</label>
                    <select v-model="campaignForm.channel" class="modern-select" required>
                      <option value="email">E-Mail Marketing</option>
                      <option value="social">Social Media</option>
                      <option value="ppc">PPC Werbung</option>
                      <option value="display">Display Advertising</option>
                      <option value="content">Content Marketing</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Zielgruppe</label>
                    <input 
                      v-model="campaignForm.target_audience" 
                      type="text" 
                      class="modern-input" 
                      placeholder="z.B. 25-35 Jahre, Tech-Interessierte"
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Startdatum</label>
                    <input 
                      v-model="campaignForm.start_date" 
                      type="date" 
                      class="modern-input" 
                      required
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Enddatum</label>
                    <input 
                      v-model="campaignForm.end_date" 
                      type="date" 
                      class="modern-input"
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Budget (€)</label>
                    <input 
                      v-model.number="campaignForm.budget" 
                      type="number" 
                      step="0.01" 
                      min="0" 
                      class="modern-input" 
                      required
                      placeholder="0.00"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Ausgegeben (€)</label>
                    <input 
                      v-model.number="campaignForm.spent" 
                      type="number" 
                      step="0.01" 
                      min="0" 
                      class="modern-input"
                      placeholder="0.00"
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Kampagnen URL</label>
                    <input 
                      v-model="campaignForm.campaign_url" 
                      type="url" 
                      class="modern-input" 
                      placeholder="https://example.com/landing-page"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">UTM Parameter</label>
                    <input 
                      v-model="campaignForm.utm_parameters" 
                      type="text" 
                      class="modern-input" 
                      placeholder="utm_campaign=summer2024"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Kampagnen Ziele</label>
                  <textarea 
                    v-model="campaignForm.goals" 
                    class="modern-textarea" 
                    rows="2"
                    placeholder="z.B. 1000 Leads generieren, 5% CTR erreichen..."
                  ></textarea>
                </div>

                <div class="form-actions">
                  <button type="button" class="action-btn secondary" @click="closeCampaignForm">
                    Abbrechen
                  </button>
                  <button type="submit" class="action-btn primary" :disabled="saving">
                    {{ saving ? 'Speichern...' : (editingCampaign ? 'Aktualisieren' : 'Erstellen') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: "MarketingCampaigns",
  data() {
    return {
      campaigns: [],
      filteredCampaigns: [],
      loading: true,
      error: null,
      searchTerm: '',
      statusFilter: '',
      channelFilter: '',
      sortField: 'created_at',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 20,
      showCampaignForm: false,
      editingCampaign: null,
      saving: false,
      dropdownOpen: false,
      
      // Form data
      campaignForm: {
        name: '',
        description: '',
        status: 'draft',
        channel: 'email',
        target_audience: '',
        start_date: '',
        end_date: '',
        budget: 0,
        spent: 0,
        campaign_url: '',
        utm_parameters: '',
        goals: ''
      },
      
      // Stats
      totalCampaigns: 0,
      activeCampaigns: 0,
      upcomingCampaigns: 0,
      totalBudget: 0,
      spentBudget: 0,
      totalImpressions: 0,
      campaignGrowth: 15,
      impressionGrowth: 23
    };
  },
  
  computed: {
    paginatedCampaigns() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredCampaigns.slice(start, end);
    },
    
    totalPages() {
      return Math.ceil(this.filteredCampaigns.length / this.itemsPerPage);
    }
  },
  
  mounted() {
    this.loadCampaigns();
  },
  
  methods: {
    async loadCampaigns() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await this.$axios.get(`marketing_campaigns.php?project=${this.$route.params.project}`);
        this.campaigns = response.data.campaigns || [];
        this.calculateStats();
        this.filteredCampaigns = [...this.campaigns];
        this.sortCampaigns();
      } catch (e) {
        this.error = e.message;
        console.error('Error loading campaigns:', e);
      } finally {
        this.loading = false;
      }
    },
    
    calculateStats() {
      this.totalCampaigns = this.campaigns.length;
      this.activeCampaigns = this.campaigns.filter(c => c.status === 'active').length;
      this.upcomingCampaigns = this.campaigns.filter(c => c.status === 'scheduled').length;
      this.totalBudget = this.campaigns.reduce((sum, c) => sum + parseFloat(c.budget || 0), 0);
      this.spentBudget = this.campaigns.reduce((sum, c) => sum + parseFloat(c.spent || 0), 0);
      this.totalImpressions = this.campaigns.reduce((sum, c) => sum + parseInt(c.impressions || 0), 0);
    },
    
    filterCampaigns() {
      let filtered = [...this.campaigns];
      
      if (this.searchTerm) {
        const search = this.searchTerm.toLowerCase();
        filtered = filtered.filter(campaign => 
          campaign.name.toLowerCase().includes(search) ||
          campaign.description.toLowerCase().includes(search) ||
          campaign.target_audience.toLowerCase().includes(search)
        );
      }
      
      if (this.statusFilter) {
        filtered = filtered.filter(campaign => campaign.status === this.statusFilter);
      }
      
      if (this.channelFilter) {
        filtered = filtered.filter(campaign => campaign.channel === this.channelFilter);
      }
      
      this.filteredCampaigns = filtered;
      this.sortCampaigns();
      this.currentPage = 1;
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.sortCampaigns();
    },
    
    sortCampaigns() {
      this.filteredCampaigns.sort((a, b) => {
        let aVal = a[this.sortField];
        let bVal = b[this.sortField];
        
        if (this.sortField === 'budget' || this.sortField === 'spent') {
          aVal = parseFloat(aVal) || 0;
          bVal = parseFloat(bVal) || 0;
        } else if (this.sortField === 'start_date' || this.sortField === 'end_date') {
          aVal = new Date(aVal);
          bVal = new Date(bVal);
        }
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return 'swap-vertical';
      return this.sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
    },
    
    getSortClass(field) {
      if (this.sortField !== field) return 'sort-default';
      return 'sort-active';
    },
    
    toggleCampaignForm() {
      this.showCampaignForm = !this.showCampaignForm;
      if (!this.showCampaignForm) {
        this.resetForm();
      }
    },
    
    closeCampaignForm() {
      this.showCampaignForm = false;
      this.resetForm();
    },
    
    resetForm() {
      this.editingCampaign = null;
      this.campaignForm = {
        name: '',
        description: '',
        status: 'draft',
        channel: 'email',
        target_audience: '',
        start_date: '',
        end_date: '',
        budget: 0,
        spent: 0,
        campaign_url: '',
        utm_parameters: '',
        goals: ''
      };
    },
    
    editCampaign(campaign) {
      this.editingCampaign = campaign;
      this.campaignForm = { ...campaign };
      this.showCampaignForm = true;
    },
    
    async saveCampaign() {
      this.saving = true;
      
      try {
        const data = {
          ...this.campaignForm,
          project: this.$route.params.project,
          action: this.editingCampaign ? 'update' : 'create'
        };
        
        if (this.editingCampaign) {
          data.id = this.editingCampaign.id;
        }
        
        await this.$axios.post('marketing_campaigns.php', this.$qs.stringify(data));
        
        this.closeCampaignForm();
        this.loadCampaigns();
      } catch (error) {
        console.error('Error saving campaign:', error);
        this.error = 'Fehler beim Speichern der Kampagne';
      } finally {
        this.saving = false;
      }
    },
    
    async deleteCampaign(campaignId) {
      if (!confirm('Sind Sie sicher, dass Sie diese Kampagne löschen möchten?')) {
        return;
      }
      
      try {
        await this.$axios.post('marketing_campaigns.php', this.$qs.stringify({
          action: 'delete',
          id: campaignId,
          project: this.$route.params.project
        }));
        
        this.loadCampaigns();
      } catch (error) {
        console.error('Error deleting campaign:', error);
        this.error = 'Fehler beim Löschen der Kampagne';
      }
    },
    
    async pauseCampaign(campaignId) {
      try {
        await this.$axios.post('marketing_campaigns.php', this.$qs.stringify({
          action: 'update_status',
          id: campaignId,
          status: 'paused',
          project: this.$route.params.project
        }));
        
        this.loadCampaigns();
      } catch (error) {
        console.error('Error pausing campaign:', error);
      }
    },
    
    async resumeCampaign(campaignId) {
      try {
        await this.$axios.post('marketing_campaigns.php', this.$qs.stringify({
          action: 'update_status',
          id: campaignId,
          status: 'active',
          project: this.$route.params.project
        }));
        
        this.loadCampaigns();
      } catch (error) {
        console.error('Error resuming campaign:', error);
      }
    },
    
    viewCampaignDetails(campaign) {
      // Future implementation for detailed view
      console.log('View details for campaign:', campaign);
    },
    
    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },
    
    openAnalyticsModal() {
      // Future implementation
      console.log('Open analytics modal');
      this.dropdownOpen = false;
    },
    
    openTemplatesModal() {
      // Future implementation
      console.log('Open templates modal');
      this.dropdownOpen = false;
    },
    
    async exportCampaigns() {
      try {
        const csvContent = [
          ['Name', 'Status', 'Kanal', 'Start', 'Ende', 'Budget', 'Ausgegeben', 'Impressionen', 'CTR'],
          ...this.filteredCampaigns.map(campaign => [
            campaign.name,
            this.getStatusText(campaign.status),
            this.getChannelText(campaign.channel),
            campaign.start_date,
            campaign.end_date || '',
            campaign.budget,
            campaign.spent,
            campaign.impressions || 0,
            campaign.click_rate || 0
          ])
        ].map(row => row.join(',')).join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `marketing_campaigns_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error exporting campaigns:', error);
      }
    },
    
    refreshCampaigns() {
      this.loadCampaigns();
    },
    
    // Helper methods
    formatDate(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString('de-DE');
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0);
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat('de-DE').format(num || 0);
    },
    
    getStatusText(status) {
      const statusMap = {
        draft: 'Entwurf',
        scheduled: 'Geplant',
        active: 'Aktiv',
        paused: 'Pausiert',
        completed: 'Abgeschlossen'
      };
      return statusMap[status] || status;
    },
    
    getChannelText(channel) {
      const channelMap = {
        email: 'E-Mail',
        social: 'Social Media',
        ppc: 'PPC Werbung',
        display: 'Display Ads',
        content: 'Content Marketing'
      };
      return channelMap[channel] || channel;
    },
    
    getChannelIcon(channel) {
      const iconMap = {
        email: 'mail-outline',
        social: 'logo-instagram',
        ppc: 'pricetag-outline',
        display: 'desktop-outline',
        content: 'document-text-outline'
      };
      return iconMap[channel] || 'megaphone-outline';
    }
  }
};
</script>

<style scoped>
/* Modern Design System - inheriting from FormDisplay.vue */
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

/* Dropdown */
.dropdown {
  position: relative;
}

.dropdown-toggle {
  padding: 10px 12px;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  min-width: 200px;
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: all 0.2s ease;
}

.dropdown-menu.active {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  color: var(--text-primary);
  text-decoration: none;
  font-size: 14px;
  cursor: pointer;
  border-bottom: 1px solid var(--border);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background: var(--background);
}

.dropdown-item ion-icon {
  font-size: 16px;
  color: var(--text-secondary);
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--shadow);
  transition: all 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.kpi-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  font-size: 24px;
}

.kpi-icon.active {
  background: var(--success-color);
}

.kpi-icon.success {
  background: var(--success-color);
}

.kpi-icon.warning {
  background: var(--warning-color);
}

.kpi-content {
  flex: 1;
}

.kpi-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.kpi-label {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.kpi-trend {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
}

.kpi-trend.positive {
  color: var(--success-color);
}

.kpi-trend.negative {
  color: var(--danger-color);
}

.kpi-subtitle {
  font-size: 12px;
  color: var(--text-muted);
}

/* Filter Bar */
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 250px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.filter-controls {
  display: flex;
  gap: 12px;
}

.modern-select {
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 150px;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
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

/* Loading and Error States */
.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loading-container ion-spinner {
  margin-bottom: 16px;
}

.error-container ion-icon {
  font-size: 48px;
  color: var(--danger-color);
  margin-bottom: 16px;
}

/* Modern Table */
.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 1000px;
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
  flex: 0 0 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  cursor: default;
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
  flex: 0 0 160px;
  justify-content: center;
  padding: 12px 16px;
}

/* Campaign specific cells */
.campaign-info {
  flex-direction: column;
  align-items: flex-start;
}

.campaign-name {
  font-weight: 600;
  margin-bottom: 4px;
}

.campaign-description {
  font-size: 12px;
  color: var(--text-secondary);
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.draft {
  background: #f3f4f6;
  color: #374151;
}

.status-badge.scheduled {
  background: #dbeafe;
  color: var(--primary-color);
}

.status-badge.active {
  background: #d1fae5;
  color: var(--success-color);
}

.status-badge.paused {
  background: #fef3c7;
  color: var(--warning-color);
}

.status-badge.completed {
  background: #e5e7eb;
  color: #6b7280;
}

.channel-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: var(--background);
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
}

.channel-badge.email {
  color: var(--primary-color);
}

.channel-badge.social {
  color: #e91e63;
}

.channel-badge.ppc {
  color: var(--warning-color);
}

.channel-badge.display {
  color: #9c27b0;
}

.channel-badge.content {
  color: var(--success-color);
}

.date-info {
  flex-direction: column;
  align-items: flex-start;
}

.date-primary {
  font-weight: 500;
  margin-bottom: 2px;
}

.date-secondary {
  font-size: 12px;
  color: var(--text-secondary);
}

.budget-info {
  flex-direction: column;
  align-items: flex-start;
}

.budget-total {
  font-weight: 600;
  margin-bottom: 2px;
}

.budget-spent {
  font-size: 12px;
  color: var(--text-secondary);
}

.performance-metrics {
  display: flex;
  gap: 16px;
}

.metric {
  text-align: center;
}

.metric-value {
  display: block;
  font-weight: 600;
  font-size: 14px;
}

.metric-label {
  display: block;
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
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

.edit-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.view-btn {
  background: #f0fdf4;
  color: var(--success-color);
}

.view-btn:hover {
  background: #dcfce7;
  transform: scale(1.05);
}

.pause-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.pause-btn:hover {
  background: #fde68a;
  transform: scale(1.05);
}

.play-btn {
  background: #d1fae5;
  color: var(--success-color);
}

.play-btn:hover {
  background: #bbf7d0;
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

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  padding: 24px;
  border-top: 1px solid var(--border);
}

.pagination span {
  font-weight: 500;
  color: var(--text-primary);
}

/* No Data State */
.no-data-state {
  padding: 80px 20px;
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
  margin-bottom: 20px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

/* Form Section */
.form-section {
  position: fixed;
  top: 0;
  right: -700px;
  width: 700px;
  height: 100vh;
  background: var(--surface);
  box-shadow: var(--shadow-lg);
  transition: right 0.3s ease;
  z-index: 1000;
  border-left: 1px solid var(--border);
}

.form-section.form-visible {
  right: 0;
}

.form-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.form-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.close-form-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: var(--border);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-form-btn:hover {
  background: var(--text-muted);
  color: var(--surface);
}

.form-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

/* Campaign Form */
.campaign-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-textarea {
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
.modern-textarea:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
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
  
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-controls {
    flex-wrap: wrap;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .modern-table {
    min-width: 800px;
  }
  
  .form-section {
    width: 100%;
    right: -100%;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .dropdown-menu {
    right: auto;
    left: 0;
  }
}

@media (max-width: 480px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .kpi-card {
    flex-direction: column;
    text-align: center;
  }
  
  .performance-metrics {
    flex-direction: column;
    gap: 8px;
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
  
  .search-input,
  .modern-input,
  .modern-textarea,
  .modern-select {
    background: var(--background);
    color: var(--text-primary);
  }
}
</style>
