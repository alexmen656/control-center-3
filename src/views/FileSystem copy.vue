<template>
    <ion-page>
        <ion-content>
            <div class="file-system-grid">
                <ion-grid>
                    <ion-row>
                        <ion-col :size="index === 6 ? '12' : '2'" :class="[item.open ? 'folder-open' : 'folder', index == 6 ? 'breit' : 'male']" v-for="(item, index) in fileSystem" :key="item.name">
                            <ion-card :class="[item.open ? 'folder-open' : 'folder', index == 6 ? 'breit' : 'male']" @click="item.type === 'folder' && toggleFolder(item)">
                                <ion-card-header>
                                    <ion-icon :name="item.type === 'folder' ? (item.open ? 'folder-open' : 'folder') : 'document'"></ion-icon>
                                    {{ item.name }}
                                </ion-card-header>
                              <!-- <ion-card-content v-if="item.open && item.type === 'folder'">
                                    <ion-grid>
                                        hj
                                        <ion-row>
                                            <ion-col size="12">
                                                <ion-grid>
                                                    <ion-row>
                                                        <ion-col size="2" v-for="subItem in item.children" :key="subItem.name">
                                                            <ion-card>
                                                                <ion-card-header>
                                                                    <ion-icon :name="subItem.type === 'folder' ? 'folder' : 'document'"></ion-icon>
                                                                    {{ subItem.name }}
                                                                </ion-card-header>
                                                            </ion-card>
                                                        </ion-col>
                                                    </ion-row>
                                                </ion-grid>
                                            </ion-col>
                                        </ion-row>
                                    </ion-grid>
                                </ion-card-content>--> 
                            </ion-card>
                        </ion-col>
                    </ion-row>
                </ion-grid>
            </div>
        </ion-content>
    </ion-page>
</template>

<script>
import { defineComponent, ref } from 'vue';
import { IonPage, IonContent, IonIcon } from '@ionic/vue';

export default defineComponent({
    name: 'FileSystem',
    components: {
        IonPage,
        IonContent,
        IonIcon,
    },
    setup() {
        const fileSystem = ref([
            {
                name: 'Folder 1',
                type: 'folder',
                open: false,
                children: [
                    { name: 'File 1-1', type: 'file' },
                    { name: 'File 1-2', type: 'file' },
                    {
                        name: 'Subfolder 1-1',
                        type: 'folder',
                        open: false,
                        children: [{ name: 'File 1-1-1', type: 'file' }],
                    },
                ],
            },
            {
                name: 'Folder 2',
                type: 'folder',
                open: false,
                children: [
                    { name: 'File 2-1', type: 'file' },
                    { name: 'File 2-2', type: 'file' },
                    {
                        name: 'Subfolder 2-1',
                        type: 'folder',
                        open: false,
                        children: [
                            { name: 'File 2-1-1', type: 'file' },
                            { name: 'File 2-1-2', type: 'file' },
                        ],
                    },
                ],
            },
            {
                name: 'Folder 3',
                type: 'folder',
                open: false,
                children: [
                    { name: 'File 3-1', type: 'file' },
                    { name: 'File 3-2', type: 'file' },
                    {
                        name: 'Subfolder 3-1',
                        type: 'folder',
                        open: false,
                        children: [
                            { name: 'File 3-1-1', type: 'file' },
                            { name: 'File 3-1-2', type: 'file' },
                            { name: 'File 3-1-3', type: 'file' },
                        ],
                    },
                ],
            },
            {
                name: 'Folder 4',
                type: 'folder',
                open: false,
                children: [
                    { name: 'File 4-1', type: 'file' },
                    { name: 'File 4-2', type: 'file' },
                    {
                        name: 'Subfolder 4-1',
                        type: 'folder',
                        open: false,
                        children: [
                            { name: 'File 4-1-1', type: 'file' },
                            { name: 'File 4-1-2', type: 'file' },
                        ],
                    },
                ],
            },
            {
                name: 'Folder 5',
                type: 'folder',
                open: false,
                children: [
                    { name: 'File 5-1', type: 'file' },
                    { name: 'File 5-2', type: 'file' },
                    {
                        name: 'Subfolder 5-1',
                        type: 'folder',
                        open: false,
                        children: [
                            { name: 'File 5-1-1', type: 'file' },
                            { name: 'File 5-1-2', type: 'file' },
                        ],
                    },
                ],
            },
            { name: 'File 6', type: 'file' },
            { name: 'File 7', type: 'file' },
            { name: 'File 8', type: 'file' },
            { name: 'File 9', type: 'file' },
            { name: 'File 10', type: 'file' },
        ]);

        const toggleFolder = (folder) => {
            folder.open = !folder.open;
        };

        return {
            fileSystem,
            toggleFolder,
        };
    },
});
</script>

<style scoped>
ion-icon {
    margin-right: 8px;
}


ion-card.folder-open {
    border: 1px solid;
    border-bottom: 0;
    box-shadow: none;
    border-radius: 20px 20px 0px 0px;
    padding-bottom: auto;
    margin-bottom: 0;
    height: 100%;
}

ion-card.breit {
    position: relative;

    box-shadow: none;
    margin: 0;
    padding: 0;
    border: 1px solid;
    height: 10rem;

  /*  border-top: 0;*/
}

ion-card.breit:before {
    content: '';
    position: absolute;
    top: -20px;
    left: 41.66%;
    transform: translateX(-50%);
    height: 50px;
    background: rebeccapurple;
    z-index: 1000;
    width: 14.1%;
}


ion-card.breit::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 41.66%;
    transform: translateX(-50%);
    height: 50px;
    background: rebeccapurple;
    z-index: 1000;
    width: 14.1%;
}

ion-col.breit {
    padding: 0;
}

ion-col.folder-open {
    padding-bottom: 0;
    margin-bottom: 0;
}
</style>