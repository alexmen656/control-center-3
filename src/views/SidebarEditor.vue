<template>
    <ion-page>
        <ion-header>
            <ion-toolbar>
                <ion-buttons slot="start">
                    <ion-back-button :default-href="'/project/' + $route.params.project + '/'"></ion-back-button>
                </ion-buttons>
                <ion-title>Sidebar Editor</ion-title>
            </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
            <ion-card v-if="loading">
                <ion-card-content class="loading-state">
                    <ion-spinner></ion-spinner>
                    <ion-label>Loading...</ion-label>
                </ion-card-content>
            </ion-card>

            <div v-else>
                <!-- Sections Management -->
                <ion-card>
                    <ion-card-header>
                        <ion-card-title>Sections</ion-card-title>
                        <ion-card-subtitle>Organize your sidebar with custom sections</ion-card-subtitle>
                    </ion-card-header>
                    <ion-card-content>
                        <ion-reorder-group :disabled="false" @ionItemReorder="handleSectionReorder($event)">
                            <ion-item v-for="section in sections" :key="section.id" class="section-item">
                                <ion-icon slot="start" :name="section.icon" color="primary"></ion-icon>
                                <ion-label>
                                    <h2>{{ section.name }}</h2>
                                    <p>{{ getSectionItemCount(section) }} items</p>
                                </ion-label>
                                <ion-badge v-if="section.is_default" color="medium" slot="end">Default</ion-badge>
                                <ion-button fill="clear" slot="end" @click="editSection(section)">
                                    <ion-icon name="create-outline"></ion-icon>
                                </ion-button>
                                <ion-button fill="clear" slot="end" @click="deleteSection(section)" color="danger"
                                    :disabled="section.is_default">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </ion-button>
                                <ion-reorder slot="end"></ion-reorder>
                            </ion-item>
                        </ion-reorder-group>

                        <div v-if="sections.length === 0" class="empty-state">
                            <ion-icon name="folder-open-outline" color="medium"></ion-icon>
                            <p>No sections yet</p>
                        </div>

                        <ion-button expand="block" fill="outline" @click="createSection" class="add-section-btn">
                            <ion-icon slot="start" name="add-outline"></ion-icon>
                            Add Section
                        </ion-button>
                    </ion-card-content>
                </ion-card>

                <!-- Section Templates -->
                <ion-card v-if="sectionTemplates.length > 0">
                    <ion-card-header>
                        <ion-card-title>Quick Add Templates</ion-card-title>
                    </ion-card-header>
                    <ion-card-content>
                        <div class="template-grid">
                            <ion-chip v-for="template in sectionTemplates" :key="template.id"
                                @click="createFromTemplate(template)" outline color="primary">
                                <ion-icon :name="template.icon"></ion-icon>
                                <ion-label>{{ template.name }}</ion-label>
                            </ion-chip>
                        </div>
                    </ion-card-content>
                </ion-card>

                <!-- Items per Section (Tools + Forms) -->
                <ion-card v-for="section in sections" :key="'items-' + section.id">
                    <ion-card-header>
                        <ion-card-title>
                            <ion-icon :name="section.icon" style="margin-right: 8px;"></ion-icon>
                            {{ section.name }} - Items
                        </ion-card-title>
                    </ion-card-header>
                    <ion-card-content>
                        <ion-reorder-group :disabled="false" @ionItemReorder="handleItemReorder($event, section.id)">
                            <ion-item v-for="item in getSectionItems(section)" :key="item.id">
                                <ion-icon slot="start" :name="item.icon || 'document-outline'"></ion-icon>
                                <ion-label>
                                    <ion-input v-model="item.name" placeholder="Item Name"
                                        @ionBlur="updateItem(item)"></ion-input>
                                    <ion-badge :color="item.item_type === 'tool' ? 'primary' : 'success'"
                                        style="margin-left: 8px;">
                                        {{ item.item_type }}
                                    </ion-badge>
                                </ion-label>
                                <ion-input slot="end" v-model="item.icon" placeholder="Icon" style="max-width: 150px;"
                                    @ionBlur="updateItem(item)"></ion-input>
                                <ion-button fill="clear" slot="end" @click="moveItemToSection(item)">
                                    <ion-icon name="swap-horizontal-outline"></ion-icon>
                                </ion-button>
                                <ion-reorder slot="end"></ion-reorder>
                            </ion-item>
                        </ion-reorder-group>
                        <div v-if="getSectionItems(section).length === 0" class="empty-tools">
                            <ion-label color="medium">No items in this section</ion-label>
                        </div>
                    </ion-card-content>
                </ion-card>

                <!-- Uncategorized Tools -->
                <ion-card v-if="uncategorizedTools.length > 0">
                    <ion-card-header>
                        <ion-card-title>
                            <ion-icon name="construct-outline" style="margin-right: 8px;"></ion-icon>
                            Uncategorized Tools
                        </ion-card-title>
                        <ion-card-subtitle>Assign these tools to a section</ion-card-subtitle>
                    </ion-card-header>
                    <ion-card-content>
                        <ion-list>
                            <ion-item v-for="tool in uncategorizedTools" :key="tool.id">
                                <ion-icon slot="start" :name="tool.icon"></ion-icon>
                                <ion-label>{{ tool.name }}</ion-label>
                                <ion-select slot="end" placeholder="Move to..."
                                    @ionChange="assignToolToSection(tool.id, $event)">
                                    <ion-select-option v-for="section in sections" :key="section.id"
                                        :value="section.id">
                                        {{ section.name }}
                                    </ion-select-option>
                                </ion-select>
                            </ion-item>
                        </ion-list>
                    </ion-card-content>
                </ion-card>

                <!-- Uncategorized Forms/Tables -->
                <ion-card v-if="uncategorizedForms.length > 0">
                    <ion-card-header>
                        <ion-card-title>
                            <ion-icon name="list-outline" style="margin-right: 8px;"></ion-icon>
                            Uncategorized Tables
                        </ion-card-title>
                        <ion-card-subtitle>Assign these tables to a section</ion-card-subtitle>
                    </ion-card-header>
                    <ion-card-content>
                        <ion-list>
                            <ion-item v-for="form in uncategorizedForms" :key="form.form_id">
                                <ion-icon slot="start" :name="form.icon || 'list-outline'"></ion-icon>
                                <ion-label>{{ form.form_name }}</ion-label>
                                <ion-select slot="end" placeholder="Move to..."
                                    @ionChange="assignFormToSection(form.form_id, $event)">
                                    <ion-select-option v-for="section in sections" :key="section.id"
                                        :value="section.id">
                                        {{ section.name }}
                                    </ion-select-option>
                                </ion-select>
                            </ion-item>
                        </ion-list>
                    </ion-card-content>
                </ion-card>

            </div>
        </ion-content>
    </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import {
    IonPage,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonList,
    IonItem,
    IonInput,
    IonLabel,
    IonIcon,
    IonButton,
    IonButtons,
    IonBackButton,
    IonSpinner,
    IonBadge,
    IonChip,
    IonReorder,
    IonReorderGroup,
    IonSelect,
    IonSelectOption,
    alertController,
    toastController
} from "@ionic/vue";

interface Tool {
    id: number;
    name: string;
    icon: string;
    hasConfig: number;
    order: number;
    section_id?: number;
}

interface Form {
    form_id: number;
    form_name: string;
    icon?: string;
    section_id?: number;
    order_index?: number;
}

interface Section {
    id: number;
    name: string;
    slug: string;
    icon: string;
    order_index: number;
    is_default: boolean;
    is_collapsible: boolean;
    show_add_button: boolean;
    add_button_route: string | null;
    info_route: string | null;
    manage_route: string | null;
    tools: Tool[];
    items?: any[];
}

interface SectionTemplate {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
}

export default defineComponent({
    name: "SidebarEditor",
    components: {
        IonPage,
        IonHeader,
        IonToolbar,
        IonTitle,
        IonContent,
        IonCard,
        IonCardHeader,
        IonCardTitle,
        IonCardSubtitle,
        IonCardContent,
        IonList,
        IonItem,
        IonInput,
        IonLabel,
        IonIcon,
        IonButton,
        IonButtons,
        IonBackButton,
        IonSpinner,
        IonBadge,
        IonChip,
        IonReorder,
        IonReorderGroup,
        IonSelect,
        IonSelectOption
    },
    setup() {
        const route = useRoute();
        const loading = ref(true);
        const sections = ref<Section[]>([]);
        const sectionTemplates = ref<SectionTemplate[]>([]);
        const uncategorizedTools = ref<Tool[]>([]);
        const uncategorizedForms = ref<Form[]>([]);

        const loadData = async () => {
            loading.value = true;
            try {
                // Load sidebar data with sections
                const sidebarResponse = await axios.get(
                    `sidebar.php?getSideBarByProjectName=${route.params.project}`
                );
                sections.value = sidebarResponse.data.sections || [];
                uncategorizedTools.value = sidebarResponse.data.tools || [];

                // Filter uncategorized forms (forms without section_id)
                // Handle null, undefined, 0, empty string, or "null" string
                const allForms = sidebarResponse.data.forms || [];
                uncategorizedForms.value = allForms.filter((f: Form) => 
                    f.section_id === null || 
                    f.section_id === undefined || 
                    f.section_id === 0 || 
                    f.section_id === '' || 
                    f.section_id === 'null' ||
                    f.section_id === '0'
                );
                console.log('All forms:', allForms);
                console.log('Uncategorized forms:', uncategorizedForms.value);

                // Load templates
                const templatesResponse = await axios.get(
                    `sidebar_sections.php?templates=1`
                );
                sectionTemplates.value = templatesResponse.data.templates || [];
            } catch (error) {
                console.error("Error loading data:", error);
            } finally {
                loading.value = false;
            }
        };

        // Get section items (tools + forms combined)
        const getSectionItems = (section: Section): any[] => {
            return section.items || section.tools || [];
        };

        // Get item count for a section
        const getSectionItemCount = (section: Section): number => {
            if (section.items) {
                return section.items.length;
            }
            return section.tools?.length || 0;
        };

        const createSection = async () => {
            const alert = await alertController.create({
                header: 'Create New Section',
                inputs: [
                    { name: 'name', type: 'text', placeholder: 'Section Name' },
                    { name: 'icon', type: 'text', placeholder: 'Icon (e.g., folder-outline)', value: 'folder-outline' },
                    { name: 'add_button_route', type: 'text', placeholder: 'Add Button Route (optional)' }
                ],
                buttons: [
                    { text: 'Cancel', role: 'cancel' },
                    {
                        text: 'Create',
                        handler: async (data) => {
                            if (!data.name) {
                                showToast('Section name is required', 'danger');
                                return false;
                            }
                            try {
                                await axios.post("sidebar_sections.php", qs.stringify({
                                    createSection: true,
                                    project: route.params.project,
                                    name: data.name,
                                    icon: data.icon || 'folder-outline',
                                    add_button_route: data.add_button_route
                                }));
                                await loadData();
                                showToast('Section created', 'success');
                            } catch (error) {
                                console.error("Error creating section:", error);
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        const createFromTemplate = async (template: SectionTemplate) => {
            try {
                await axios.post("sidebar_sections.php", qs.stringify({
                    createSection: true,
                    project: route.params.project,
                    name: template.name,
                    icon: template.icon,
                    slug: template.slug
                }));
                await loadData();
                showToast(`Section "${template.name}" created`, 'success');
            } catch (error) {
                console.error("Error creating from template:", error);
            }
        };

        const editSection = async (section: Section) => {
            const alert = await alertController.create({
                header: 'Edit Section',
                inputs: [
                    { name: 'name', type: 'text', placeholder: 'Name', value: section.name },
                    { name: 'icon', type: 'text', placeholder: 'Icon', value: section.icon },
                    { name: 'add_button_route', type: 'text', placeholder: 'Add Button Route', value: section.add_button_route || '' },
                    { name: 'info_route', type: 'text', placeholder: 'Info Route', value: section.info_route || '' },
                    { name: 'manage_route', type: 'text', placeholder: 'Manage Route', value: section.manage_route || '' }
                ],
                buttons: [
                    { text: 'Cancel', role: 'cancel' },
                    {
                        text: 'Save',
                        handler: async (data) => {
                            try {
                                await axios.post("sidebar_sections.php", qs.stringify({
                                    updateSection: true,
                                    project: route.params.project,
                                    section_id: section.id,
                                    name: data.name,
                                    icon: data.icon,
                                    add_button_route: data.add_button_route,
                                    info_route: data.info_route,
                                    manage_route: data.manage_route
                                }));
                                await loadData();
                                showToast('Section updated', 'success');
                            } catch (error) {
                                console.error("Error updating section:", error);
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        const deleteSection = async (section: Section) => {
            const alert = await alertController.create({
                header: 'Delete Section?',
                message: `Tools in "${section.name}" will become uncategorized.`,
                buttons: [
                    { text: 'Cancel', role: 'cancel' },
                    {
                        text: 'Delete',
                        role: 'destructive',
                        handler: async () => {
                            try {
                                await axios.post("sidebar_sections.php", qs.stringify({
                                    deleteSection: true,
                                    project: route.params.project,
                                    section_id: section.id
                                }));
                                await loadData();
                                showToast('Section deleted', 'success');
                            } catch (error) {
                                console.error("Error deleting section:", error);
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        const handleSectionReorder = async (event: CustomEvent) => {
            const from = event.detail.from;
            const to = event.detail.to;
            const movedSection = sections.value.splice(from, 1)[0];
            sections.value.splice(to, 0, movedSection);
            const order = sections.value.map(s => s.id);
            try {
                await axios.post("sidebar_sections.php", qs.stringify({
                    reorderSections: true,
                    project: route.params.project,
                    order: JSON.stringify(order)
                }));
            } catch (error) {
                console.error("Error saving order:", error);
            }
            event.detail.complete();
        };

        const handleToolReorder = async (event: CustomEvent, sectionId: number) => {
            const section = sections.value.find(s => s.id === sectionId);
            if (section && section.tools) {
                const from = event.detail.from;
                const to = event.detail.to;
                const movedTool = section.tools.splice(from, 1)[0];
                section.tools.splice(to, 0, movedTool);
                // Save tool order
                try {
                    await axios.post("sidebar_sections.php", qs.stringify({
                        reorderToolsInSection: true,
                        project: route.params.project,
                        section_id: sectionId,
                        tool_order: JSON.stringify(section.tools.map(t => t.id))
                    }));
                } catch (error) {
                    console.error("Error saving tool order:", error);
                }
            }
            event.detail.complete();
        };

        const updateTool = async (tool: Tool) => {
            try {
                await axios.post("update_sidebar.php", qs.stringify({
                    project: route.params.project,
                    updateTool: true,
                    tool_id: tool.id,
                    name: tool.name,
                    icon: tool.icon
                }));
            } catch (error) {
                console.error("Error updating tool:", error);
            }
        };

        const moveToolToSection = async (tool: Tool) => {
            const alert = await alertController.create({
                header: 'Move Tool',
                message: `Move "${tool.name}" to another section`,
                inputs: sections.value.map(s => ({
                    type: 'radio' as const,
                    label: s.name,
                    value: s.id,
                    checked: s.id === tool.section_id
                })),
                buttons: [
                    { text: 'Cancel', role: 'cancel' },
                    {
                        text: 'Move',
                        handler: async (sectionId) => {
                            if (sectionId) {
                                try {
                                    await axios.post("sidebar_sections.php", qs.stringify({
                                        assignToolToSection: true,
                                        project: route.params.project,
                                        tool_id: tool.id,
                                        section_id: sectionId
                                    }));
                                    await loadData();
                                    showToast('Tool moved', 'success');
                                } catch (error) {
                                    console.error("Error moving tool:", error);
                                }
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        const updateFormIcon = async (form: any) => {
            try {
                await axios.post("sidebar_sections.php", qs.stringify({
                    updateFormSidebar: true,
                    project: route.params.project,
                    form_id: form.form_id,
                    icon: form.icon
                }));
                showToast('Icon updated', 'success');
            } catch (error) {
                console.error("Error updating form icon:", error);
            }
        };

        // Update item (tool or form)
        const updateItem = async (item: any) => {
            if (item.item_type === 'tool') {
                await updateTool(item);
            } else if (item.item_type === 'form') {
                await updateFormIcon(item);
            }
        };

        // Move item to different section
        const moveItemToSection = async (item: any) => {
            const alert = await alertController.create({
                header: 'Move Item',
                message: `Move "${item.name}" to another section`,
                inputs: [
                    ...sections.value.map(s => ({
                        type: 'radio' as const,
                        label: s.name,
                        value: s.id,
                        checked: s.id === item.section_id
                    })),
                    {
                        type: 'radio' as const,
                        label: 'Remove from section',
                        value: 0,
                        checked: !item.section_id
                    }
                ],
                buttons: [
                    { text: 'Cancel', role: 'cancel' },
                    {
                        text: 'Move',
                        handler: async (sectionId) => {
                            try {
                                const endpoint = item.item_type === 'tool' ? 'assignToolToSection' : 'assignFormToSection';
                                const idField = item.item_type === 'tool' ? 'tool_id' : 'form_id';
                                const itemId = item.item_type === 'tool' ? item.id : item.form_id;

                                await axios.post("sidebar_sections.php", qs.stringify({
                                    [endpoint]: true,
                                    project: route.params.project,
                                    [idField]: itemId,
                                    section_id: sectionId || 0
                                }));
                                await loadData();
                                showToast('Item moved', 'success');
                            } catch (error) {
                                console.error("Error moving item:", error);
                            }
                        }
                    }
                ]
            });
            await alert.present();
        };

        // Handle item reorder within section
        const handleItemReorder = async (event: CustomEvent, sectionId: number) => {
            const section = sections.value.find(s => s.id === sectionId);
            if (section) {
                const items = section.items || section.tools || [];
                const from = event.detail.from;
                const to = event.detail.to;
                const movedItem = items.splice(from, 1)[0];
                items.splice(to, 0, movedItem);

                // Build order data for backend
                const itemOrder = items.map((item: any, index: number) => ({
                    id: item.item_type === 'tool' ? item.id : item.form_id,
                    type: item.item_type || 'tool',
                    order: index
                }));

                try {
                    await axios.post("sidebar_sections.php", qs.stringify({
                        reorderSectionItems: true,
                        project: route.params.project,
                        section_id: sectionId,
                        item_order: JSON.stringify(itemOrder)
                    }));
                } catch (error) {
                    console.error("Error saving item order:", error);
                }
            }
            event.detail.complete();
        };

        const showToast = async (message: string, color: string) => {
            const toast = await toastController.create({ message, duration: 2000, color });
            await toast.present();
        };

        // Assign tool to section (from uncategorized list)
        const assignToolToSection = async (toolId: number, event: CustomEvent) => {
            const sectionId = event.detail.value;
            try {
                await axios.post("sidebar_sections.php", qs.stringify({
                    assignToolToSection: true,
                    project: route.params.project,
                    tool_id: toolId,
                    section_id: sectionId
                }));
                await loadData();
                showToast('Tool assigned to section', 'success');
            } catch (error) {
                console.error("Error assigning tool:", error);
            }
        };

        // Assign form to section (from uncategorized list)
        const assignFormToSection = async (formId: number, event: CustomEvent) => {
            const sectionId = event.detail.value;
            try {
                await axios.post("sidebar_sections.php", qs.stringify({
                    assignFormToSection: true,
                    project: route.params.project,
                    form_id: formId,
                    section_id: sectionId
                }));
                await loadData();
                showToast('Table assigned to section', 'success');
            } catch (error) {
                console.error("Error assigning form:", error);
            }
        };

        onMounted(() => {
            loadData();
        });

        return {
            loading,
            sections,
            sectionTemplates,
            uncategorizedTools,
            uncategorizedForms,
            createSection,
            createFromTemplate,
            editSection,
            deleteSection,
            handleSectionReorder,
            handleToolReorder,
            handleItemReorder,
            updateTool,
            moveToolToSection,
            updateFormIcon,
            updateItem,
            moveItemToSection,
            assignToolToSection,
            assignFormToSection,
            getSectionItems,
            getSectionItemCount
        };
    }
});
</script>

<style scoped>
.loading-state {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-item h2 {
    font-weight: 600;
}

.section-item p {
    font-size: 12px;
    color: var(--ion-color-medium);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px;
    text-align: center;
}

.empty-state ion-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.empty-state p {
    color: var(--ion-color-medium);
    margin: 0;
}

.add-section-btn {
    margin-top: 16px;
}

.template-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.template-grid ion-chip {
    cursor: pointer;
}

.empty-tools {
    padding: 16px;
    text-align: center;
}
</style>