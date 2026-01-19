<script setup>
import { ref, computed, onMounted } from 'vue';
import { useFetch } from '@/composables/vueFetch';
import { useMediaLibraryStore } from '@/stores/media-library';
import { useProjectStore } from '@/stores/project';

const mediaLibraryStore = useMediaLibraryStore();
const projectStore = useProjectStore();
const { get, post } = useFetch();
const fileSystemData = ref([]);
const currentPath = ref([]);
const loading = ref(false);
const error = ref(null);
const signedUrls = ref({});
const projectID = ref(null);

const fetchFileSystem = async () => {
    loading.value = true;
    error.value = null;
    try {
        const currentProject = projectStore.getCurrentProject;
        const projectLink = currentProject?.control_center_project?.link;

        let endpoint = '../filesystem.php';
        if (projectLink) {
            endpoint += '?project=' + encodeURIComponent(projectLink);
        }

        const response = await get(endpoint);
        fileSystemData.value = response;
        currentPath.value = [];

        if (response && response.length > 0) {
            const findProjectID = (items) => {
                for (const item of items) {
                    if (item.projectID) return item.projectID;
                    if (item.children) {
                        const id = findProjectID(item.children);
                        if (id) return id;
                    }
                }
                return null;
            };
            projectID.value = findProjectID(response);
        }

        await loadSignedUrls();
    } catch (err) {
        console.error(err);
        error.value = 'Fehler beim Laden des Dateisystems.';
    } finally {
        loading.value = false;
    }
};

const loadSignedUrls = async () => {
    const imageFiles = [];

    const collectImages = (items) => {
        items.forEach(item => {
            if (item.type === 'file' && isImage(item.name)) {
                imageFiles.push({
                    path: item.location,
                    location: item.location,
                    projectID: projectID.value
                });
            }
            if (item.type === 'folder' && item.children) {
                collectImages(item.children);
            }
        });
    };

    collectImages(fileSystemData.value);

    if (imageFiles.length === 0) return;

    try {
        const response = await post('../signed_url_generator.php', {
            files: imageFiles,
            validitySeconds: 3600
        });

        if (response.success) {
            response.urls.forEach(item => {
                signedUrls.value[item.originalPath] = item.signedUrl;
            });
        }
    } catch (error) {
        console.error('Error loading signed URLs:', error);
    }
};

const getSignedImageUrl = (location) => {
    return signedUrls.value[location] || '';
};

const currentFolderContents = computed(() => {
    if (currentPath.value.length === 0) {
        return fileSystemData.value;
    }
    const currentFolder = currentPath.value[currentPath.value.length - 1];
    return currentFolder.children || [];
});

const navigateToFolder = (folder) => {
    currentPath.value.push(folder);
};

const navigateToBreadcrumb = (index) => {
    if (index === -1) {
        currentPath.value = [];
    } else {
        currentPath.value = currentPath.value.slice(0, index + 1);
    }
};

const isImage = (filename) => {
    return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(filename);
};

const selectFile = (file) => {
    if (isImage(file.name)) {
        const imageUrl = getSignedImageUrl(file.location);

        if (imageUrl) {
            const imageObject = {
                file: imageUrl,
                filename: file.name,
                type: 'filesystem',
                location: file.location
            };

            mediaLibraryStore.setCurrentImage(imageObject);
            mediaLibraryStore.setCurrentPreviewImage(imageUrl);
        }
    }
};

onMounted(() => {
    fetchFileSystem();
});
</script>

<template>
    <div class="h-full flex flex-col w-full">
        <div class="flex items-center gap-2 p-3 border-b border-gray-200 overflow-x-auto bg-gray-50 rounded-t-lg">
            <button @click="navigateToBreadcrumb(-1)"
                class="text-myPrimaryLinkColor hover:underline whitespace-nowrap text-sm font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-lg">folder</span> Filesystem
            </button>
            <span v-if="currentPath.length > 0" class="text-gray-400">/</span>
            <template v-for="(folder, index) in currentPath" :key="folder.id">
                <button @click="navigateToBreadcrumb(index)"
                    class="text-myPrimaryLinkColor hover:underline whitespace-nowrap text-sm font-medium">
                    {{ folder.name }}
                </button>
                <span v-if="index < currentPath.length - 1" class="text-gray-400">/</span>
            </template>
        </div>
        <div class="flex-1 overflow-y-auto p-4 min-h-[300px]">
            <div v-if="loading" class="flex justify-center p-10">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            </div>
            <div v-else-if="error" class="text-red-500 p-4 text-center">
                {{ error }}
                <button @click="fetchFileSystem" class="block mx-auto mt-2 text-blue-600 underline">Erneut
                    versuchen</button>
            </div>
            <div v-else-if="currentFolderContents.length === 0"
                class="text-gray-500 text-center p-10 flex flex-col items-center">
                <span class="material-symbols-outlined text-4xl mb-2">folder_off</span>
                <span>Dieser Ordner ist leer</span>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div v-for="item in currentFolderContents" :key="item.id"
                    class="group border border-gray-200 rounded-lg p-3 cursor-pointer hover:shadow-md transition-all duration-200 flex flex-col items-center text-center bg-white"
                    :class="{
                        'ring-2 ring-myPrimaryLinkColor bg-blue-50': mediaLibraryStore.currentImage?.filename === item.name && item.type !== 'folder'
                    }" @click="item.type === 'folder' ? navigateToFolder(item) : selectFile(item)">
                    <div
                        class="mb-3 w-full aspect-square flex items-center justify-center overflow-hidden rounded bg-gray-50 relative">
                        <span v-if="item.type === 'folder'"
                            class="material-symbols-outlined text-5xl text-yellow-500 group-hover:scale-110 transition-transform">folder</span>

                        <img v-else-if="isImage(item.name) && getSignedImageUrl(item.location)"
                            :src="getSignedImageUrl(item.location)"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy" />

                        <span v-else class="material-symbols-outlined text-5xl text-gray-400">description</span>
                    </div>
                    <div class="text-xs font-medium text-gray-700 truncate w-full px-1" :title="item.name">
                        {{ item.name }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
