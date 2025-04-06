<template>
    <ion-page>
      <ion-header>
        <ion-toolbar color="primary">
          <ion-title>Notepad</ion-title>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <!-- New Note Form -->
        <ion-card class="note-form-card">
          <ion-card-header>
            <ion-card-title>{{ isEditing ? 'Edit Note' : 'New Note' }}</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-item>
              <ion-label position="stacked">Header</ion-label>
              <ion-input v-model="newNote.header" placeholder="Enter header"></ion-input>
            </ion-item>
            <!--<ion-item>
              <ion-label position="stacked">Image URL</ion-label>
              <ion-input v-model="newNote.image" placeholder="Enter image URL"></ion-input>
            </ion-item>-->
            <ion-item class="editor-container">
             <!-- <ion-label position="stacked">Content (HTML allowed)</ion-label>-->
              <div class="quill-wrapper">
                <QuillEditor 
                  ref="quillEditor"
                  contentType="html" 
                  v-model:content="newNote.content" 
                  :options="editorOptions"
                />
              </div>
            </ion-item>
            <div class="button-container">
              <ion-button expand="block" @click="saveNote">
                {{ isEditing ? 'Update Note' : 'Save Note' }}
              </ion-button>
              <ion-button v-if="isEditing" expand="block" fill="outline" color="medium" @click="cancelEdit">
                Cancel
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>
        
        <!-- Masonry Layout for Notes -->
        <div class="masonry-container">
          <div v-for="(note, index) in notes" :key="index" class="masonry-item">
            <ion-card>
              <img v-if="note.image" :src="note.image" class="note-image" />
              <ion-card-content>
                <div class="note-actions">
                  <ion-button fill="clear" size="small" @click="editNote(note)">
                    <ion-icon name="create-outline"></ion-icon>
                  </ion-button>
                  <ion-button fill="clear" size="small" color="danger" @click="confirmDeleteNote(note.id)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </ion-button>
                </div>
                <h2 v-if="note.header" class="note-header">{{ note.header }}</h2>
                <div v-if="note.content" class="note-content" v-html="note.content"></div>
              </ion-card-content>
            </ion-card>
          </div>
        </div>

        <!-- Delete Confirmation Alert -->
        <ion-alert
          :is-open="showDeleteAlert"
          header="Confirm Delete"
          message="Are you sure you want to delete this note?"
          :buttons="[
            {
              text: 'Cancel',
              role: 'cancel',
              handler: () => { showDeleteAlert = false; }
            },
            {
              text: 'Delete',
              role: 'confirm',
              handler: () => { deleteNote(); }
            }
          ]"
          @didDismiss="showDeleteAlert = false"
        ></ion-alert>
      </ion-content>
    </ion-page>
  </template>
  
  <script>
  import { QuillEditor } from '@vueup/vue-quill'
  import '@vueup/vue-quill/dist/vue-quill.snow.css'
  import { IonAlert } from '@ionic/vue'
  import { alertController } from '@ionic/vue'

  export default {
    name: "NotePad",
    components: {
      QuillEditor,
      IonAlert
    },
    data() {
      return {
        newNote: {
          id: null,
          header: "",
          image: "",
          content: ""
        },
        editorOptions: {
          theme: 'snow',
          modules: {
            toolbar: [
              ['bold', 'italic', 'underline', 'strike'],
              ['blockquote', 'code-block'],
              [{ 'header': 1 }, { 'header': 2 }],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              [{ 'color': [] }, { 'background': [] }],
              ['link', 'image'],
              ['clean']
            ]
          },
          placeholder: 'Write your note content here...'
        },
        notes: [],
        errorMessage: "",
        isEditing: false,
        showDeleteAlert: false,
        noteToDeleteId: null
      };
    },
    mounted() {
      this.loadNotes();
    },
    methods: {
      loadNotes() {
        this.$axios
          .get("notepad.php")
          .then(response => {
            if(response.data.status === "success") {
              this.notes = response.data.notes;
            } else {
              alert("Error loading notes: " + response.data.message);
            }
          })
          .catch(error => {
            console.error(error);
            alert("Error loading notes");
          });
      },
      
      editNote(note) {
        this.isEditing = true;
        this.newNote = {
          id: note.id,
          header: note.header || "",
          image: note.image || "",
          content: note.content || ""
        };
        
        // Scroll to the form
        const formElement = document.querySelector('.note-form-card');
        if (formElement) {
          formElement.scrollIntoView({ behavior: 'smooth' });
        }
      },
      
      cancelEdit() {
        this.isEditing = false;
        this.resetForm();
      },
      
      resetForm() {
        this.newNote = {
          id: null,
          header: "",
          image: "",
          content: ""
        };
        
        if (this.$refs.quillEditor && this.$refs.quillEditor.setContents) {
          this.$refs.quillEditor.setContents([]);
        }
      },
      
      confirmDeleteNote(id) {
        this.noteToDeleteId = id;
        this.showDeleteAlert = true;
      },
      
      deleteNote() {
        if (!this.noteToDeleteId) return;
        
        const postData = this.$qs.stringify({
          id: this.noteToDeleteId,
          action: 'delete'
        });
        
        this.$axios
          .post("notepad.php", postData)
          .then(response => {
            if(response.data.status === "success") {
              //alert("Note deleted successfully!");
              this.loadNotes();
            } else {
              alert("Error deleting note: " + response.data.message);
            }
          })
          .catch(error => {
            console.error(error);
            alert("Error deleting note");
          });
          
        this.noteToDeleteId = null;
        this.showDeleteAlert = false;
      },
      
      saveNote() {
        // Get HTML content directly from the editor
        let contentToSave = '';
        
        if (this.$refs.quillEditor && this.$refs.quillEditor.getHTML) {
          contentToSave = this.$refs.quillEditor.getHTML();
        } else {
          contentToSave = this.newNote.content || '';
        }
        
        const trimmedHeader = this.newNote.header.trim();
        const trimmedImage = this.newNote.image.trim();
        
        // Validate before saving
        if (!trimmedHeader && !contentToSave.trim()) {
          alert("Please enter either a header or content for your note.");
          return;
        }
        
        const postData = this.$qs.stringify({
          id: this.isEditing ? this.newNote.id : null,
          header: trimmedHeader,
          image: trimmedImage,
          content: contentToSave,
          action: this.isEditing ? 'update' : 'create'
        });
        
        this.$axios
          .post("notepad.php", postData)
          .then(response => {
            if(response.data.status === "success") {
              //alert(this.isEditing ? "Note updated successfully!" : "Note saved successfully!");
              this.resetForm();
              this.isEditing = false;
              this.loadNotes();
            } else {
              alert("Error saving note: " + response.data.message);
            }
          })
          .catch(error => {
            console.error(error);
            alert("Error saving note");
          });
      }
    }
  };
  </script>
  
  <style scoped>
  .masonry-container {
    column-count: 3;
    column-gap: 0rem;
    margin-top: 1.5rem;
  }
  
  .masonry-item {
    break-inside: avoid;
    margin-bottom: 1rem;
    animation: fadeIn 0.5s ease-in;
  }
  
  .note-image {
    width: 100%;
    display: block;
    margin-bottom: 0.5rem;
  }
  
  .note-content {
    max-height: 450px; /* limit note content height */
    overflow: auto;
  }

  /* Basic styles for HTML formatting inside note content */
  .note-content h1,
  .note-content h2,
  .note-content h3,
  .note-content ul,
  .note-content li,
  .note-content p {
    margin: 0.5rem 0;
  }
  
  .note-header {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: #2d3748;
  }
  
  ion-content {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
  }
  
  ion-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  ion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }
  
  /* New styles for the note form */
  .note-form-card {
    margin-bottom: 2rem;
  }
  
  .editor-container {
    --padding-start: 0;
    --padding-end: 0;
    --inner-padding-end: 0;
    width: 100%;
  }
  
  .quill-wrapper {
    width: 100%;
    margin: 1rem 0;
  }
  
  .quill-wrapper :deep(.ql-container) {
    min-height: 150px;
    max-height: 300px;
    overflow-y: auto;
  }
  
  .quill-wrapper :deep(.ql-editor) {
    min-height: 150px;
    background-color: white;
  }
  
  .quill-wrapper :deep(.ql-toolbar) {
    background-color: #f1f5f9;
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
  }
  
  .quill-wrapper {
    z-index: 1;
    position: relative;
    width: 100%;
  }
  
  .button-container {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  /* Responsive breakpoints */
  @media (max-width: 768px) {
    .masonry-container {
      column-count: 2;
    }
  }
  
  @media (max-width: 480px) {
    .masonry-container {
      column-count: 1;
    }
    
    .quill-wrapper :deep(.ql-container) {
      min-height: 120px;
    }
  }

  ion-card-content {
    padding: 1rem !important;
    position: relative;
  }

  .note-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  
  ion-card:hover .note-actions {
    opacity: 1;
  }

  ion-card-content {
    padding: 0.25rem !important;
  }
  </style>