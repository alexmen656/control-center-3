<template>
  <div class="template-selector">
    <h3 class="section-title">Project Template</h3>
    
    <!-- Loading state -->
    <div v-if="loading" class="loading-container">
      <ion-spinner name="crescent"></ion-spinner>
      <p>Loading templates...</p>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <ion-icon name="alert-circle-outline" color="danger"></ion-icon>
      <p>{{ error }}</p>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="templates.length === 0" class="empty-state">
      <ion-icon name="cube-outline" size="large"></ion-icon>
      <p>No templates available</p>
    </div>
    
    <!-- Templates list -->
    <div v-else class="templates-container">
      <!-- Template categories -->
      <div class="category-tabs">
        <ion-segment v-model="selectedCategory">
          <ion-segment-button value="all">
            <ion-label>All</ion-label>
          </ion-segment-button>
          <ion-segment-button v-for="category in categories" :key="category" :value="category">
            <ion-label>{{ getCategoryName(category) }}</ion-label>
          </ion-segment-button>
        </ion-segment>
      </div>
      
      <!-- Template cards -->
      <div class="template-cards">
        <ion-card 
          v-for="template in filteredTemplates" 
          :key="template.id"
          :class="{ 'selected': selectedTemplateId === template.id }"
          @click="selectTemplate(template.id)"
        >
          <img v-if="template.thumbnail" :src="template.thumbnail" :alt="template.name" class="template-image" />
          <div v-else class="template-placeholder-image">
            <ion-icon :name="getCategoryIcon(template.category)"></ion-icon>
          </div>
          <ion-card-header>
            <ion-card-title>{{ template.name }}</ion-card-title>
            <ion-card-subtitle>{{ template.description }}</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            <div class="component-stats">
              <div class="stat-item" v-if="countComponentsByType(template, 'tool') > 0">
                <ion-icon name="build-outline"></ion-icon>
                <span>{{ countComponentsByType(template, 'tool') }} Tools</span>
              </div>
              <div class="stat-item" v-if="countComponentsByType(template, 'page') > 0">
                <ion-icon name="document-outline"></ion-icon>
                <span>{{ countComponentsByType(template, 'page') }} {{ countComponentsByType(template, 'page') == 1 ? 'Page' : 'Pages'}}</span>
              </div>
              <div class="stat-item" v-if="countComponentsByType(template, 'service') > 0">
                <ion-icon name="server-outline"></ion-icon>
                <span>{{ countComponentsByType(template, 'service') }} Services</span>
              </div>
              <div class="stat-item" v-if="countComponentsByType(template, 'api') > 0">
                <ion-icon name="code-slash-outline"></ion-icon>
                <span>{{ countComponentsByType(template, 'api') }} APIs</span>
              </div>
            </div>
          </ion-card-content>
        </ion-card>
      </div>
    </div>
    
    <!-- Template details panel (if a template is selected) -->
    <div v-if="selectedTemplate" class="template-details">
      <h4>Template Details</h4>
      
      <div class="component-group" v-if="getComponentsByType(selectedTemplate, 'tool').length > 0">
        <h5>Tools</h5>
        <ion-list>
          <ion-item v-for="component in getComponentsByType(selectedTemplate, 'tool')" :key="'tool-'+component.id">
            <ion-icon :name="component.icon || 'build-outline'" slot="start"></ion-icon>
            <ion-label>{{ component.name }}</ion-label>
          </ion-item>
        </ion-list>
      </div>
      
      <div class="component-group" v-if="getComponentsByType(selectedTemplate, 'service').length > 0">
        <h5>Services</h5>
        <ion-list>
          <ion-item v-for="component in getComponentsByType(selectedTemplate, 'service')" :key="'service-'+component.id">
            <ion-icon :name="component.icon || 'server-outline'" slot="start"></ion-icon>
            <ion-label>{{ component.name }}</ion-label>
          </ion-item>
        </ion-list>
      </div>
      
      <div class="component-group" v-if="getComponentsByType(selectedTemplate, 'api').length > 0">
        <h5>APIs</h5>
        <ion-list>
          <ion-item v-for="component in getComponentsByType(selectedTemplate, 'api')" :key="'api-'+component.id">
            <ion-icon :name="component.icon || 'code-outline'" slot="start"></ion-icon>
            <ion-label>{{ component.name }}</ion-label>
          </ion-item>
        </ion-list>
      </div>
      
      <div class="component-group" v-if="getComponentsByType(selectedTemplate, 'page').length > 0">
        <h5>Pages</h5>
        <ion-list>
          <ion-item v-for="component in getComponentsByType(selectedTemplate, 'page')" :key="'page-'+component.id">
            <ion-icon :name="component.icon || 'document-outline'" slot="start"></ion-icon>
            <ion-label>{{ component.name }}</ion-label>
            <ion-badge v-if="component.config && component.config.is_home" color="primary" slot="end">Homepage</ion-badge>
          </ion-item>
        </ion-list>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import axios from 'axios';
import { defineComponent, ref, computed, onMounted } from 'vue';

export default defineComponent({
  name: 'ProjectTemplateSelector',
  props: {
    modelValue: {
      type: [Number, null],
      default: null
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const templates = ref<any[]>([]);
    const selectedTemplateId = ref<number | null>(props.modelValue);
    const selectedCategory = ref('all');

    const categories = computed(() => {
      const uniqueCategories = new Set<string>();
      templates.value.forEach(template => {
        if (template.category) {
          uniqueCategories.add(template.category);
        }
      });
      return Array.from(uniqueCategories);
    });

    const filteredTemplates = computed(() => {
      if (selectedCategory.value === 'all') {
        return templates.value;
      }
      return templates.value.filter(template => template.category === selectedCategory.value);
    });

    const selectedTemplate = computed(() => {
      if (!selectedTemplateId.value) return null;
      return templates.value.find(t => t.id === selectedTemplateId.value) || null;
    });

    const getCategoryName = (category: string) => {
      // Map category codes to display names
      const categoryMap: Record<string, string> = {
        'basic': 'Basic',
        'business': 'Business',
        'web': 'Web',
        'devops': 'DevOps',
        'general': 'General'
      };
      return categoryMap[category] || category.charAt(0).toUpperCase() + category.slice(1);
    };

    const getCategoryIcon = (category: string) => {
      // Map categories to appropriate icons
      const iconMap: Record<string, string> = {
        'basic': 'cube-outline',
        'business': 'briefcase-outline',
        'web': 'globe-outline',
        'devops': 'server-outline',
        'general': 'apps-outline'
      };
      return iconMap[category] || 'cube-outline';
    };

    const fetchTemplates = async () => {
      try {
        loading.value = true;
        error.value = null;
        
        const response = await axios.get('project_templates.php', {
          params: { action: 'list' }
        });
        
        if (response.data.success && response.data.templates) {
          templates.value = response.data.templates;
        } else {
          error.value = response.data.message || 'Error loading templates';
        }
      } catch (err) {
        console.error('Error fetching templates:', err);
        error.value = 'Network or server error while loading templates';
      } finally {
        loading.value = false;
      }
    };
    
    const selectTemplate = (id: number) => {
      selectedTemplateId.value = id;
      emit('update:modelValue', id);
    };
    
    const selectCategory = (category: string) => {
      selectedCategory.value = category;
    };
    
    const countComponentsByType = (template: any, type: string) => {
      if (!template.components) return 0;
      return template.components.filter(comp => comp.component_type === type).length;
    };
    
    const getComponentsByType = (template: any, type: string) => {
      if (!template || !template.components) return [];
      return template.components.filter(comp => comp.component_type === type);
    };
    
    onMounted(() => {
      fetchTemplates();
    });

    return {
      loading,
      error,
      templates,
      selectedTemplateId,
      selectedCategory,
      categories,
      filteredTemplates,
      selectedTemplate,
      getCategoryName,
      getCategoryIcon,
      selectTemplate,
      selectCategory,
      countComponentsByType,
      getComponentsByType
    };
  }
});
</script>

<style scoped>
.template-selector {
  margin-bottom: 24px;
}

.section-title {
  margin-bottom: 16px;
  font-weight: 600;
  color: var(--ion-color-dark);
}

.loading-container, .error-container, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  text-align: center;
  border-radius: 8px;
  background: var(--ion-color-light);
}

.loading-container ion-spinner {
  margin-bottom: 16px;
}

.error-container ion-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state ion-icon {
  font-size: 48px;
  margin-bottom: 16px;
  color: var(--ion-color-medium);
}

.category-tabs {
  margin-bottom: 16px;
}

.template-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

ion-card {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  margin: 0;
}

ion-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

ion-card.selected {
  border: 2px solid var(--ion-color-primary);
  box-shadow: 0 4px 10px rgba(var(--ion-color-primary-rgb), 0.2);
}

.template-image {
  height: 120px;
  object-fit: cover;
  width: 100%;
}

.template-placeholder-image {
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--ion-color-light);
}

.template-placeholder-image ion-icon {
  font-size: 48px;
  color: var(--ion-color-medium);
}

.component-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
}

.stat-item {
  display: flex;
  align-items: center;
  background: var(--ion-color-light);
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
}

.stat-item ion-icon {
  margin-right: 4px;
  font-size: 14px;
}

.template-details {
  border-top: 1px solid var(--ion-color-light);
  padding-top: 24px;
  margin-top: 16px;
}

.component-group {
  margin-bottom: 16px;
}

.component-group h5 {
  margin-top: 0;
  margin-bottom: 8px;
  color: var(--ion-color-medium-shade);
  font-size: 14px;
  font-weight: 600;
}
</style>