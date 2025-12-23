<template>
    <ion-page>
      <ion-content class="modern-content">
        <div class="page-container">
          <!-- Header Section -->
          <div class="page-header">
            <div class="header-content">
              <div class="header-info">
                <h1 class="page-title">
                  <ion-icon name="document-text-outline"></ion-icon>
                  Notepad
                </h1>
              </div>
            </div>
          </div>

          <!-- Action Bar -->
          <div class="action-bar">
            <button class="action-btn primary" @click="scrollToForm">
              <ion-icon name="add-outline"></ion-icon>
              {{ isEditing ? 'Editing Note' : 'New Note' }}
            </button>
          </div>

          <!-- New Note Form Card -->
          <div class="data-card note-form-card">
            <div class="card-header">
              <div class="header-left">
                <h3>{{ isEditing ? 'Edit Note' : 'Create New Note' }}</h3>
                <span class="form-description">Use the rich text editor to create formatted notes</span>
              </div>
            </div>
            
            <div class="card-content">
              <div class="modern-form">
                <div class="form-group">
                  <label class="form-label">Note Title</label>
                  <input 
                    v-model="newNote.header" 
                    type="text"
                    placeholder="Enter a descriptive title for your note..."
                    class="modern-input"
                  />
                </div>
                
                <div class="form-group editor-group">
                  <label class="form-label">Content</label>
                  <div class="editor-wrapper">
                    <QuillEditor 
                      ref="quillEditor"
                      contentType="html" 
                      v-model:content="newNote.content" 
                      :options="editorOptions"
                      class="modern-editor"
                    />
                  </div>
                </div>
                
                <div class="form-actions">
                  <button 
                    v-if="isEditing" 
                    class="action-btn secondary" 
                    @click="cancelEdit"
                  >
                    Cancel
                  </button>
                  <button class="action-btn primary" @click="saveNote">
                    <ion-icon name="save-outline"></ion-icon>
                    {{ isEditing ? 'Update Note' : 'Save Note' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Notes Grid -->
          <div class="notes-section">
            <div class="section-header">
              <h3>Your Notes</h3>
              <span class="notes-count">{{ notes.length }} notes</span>
            </div>
            
            <div v-if="notes.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="document-outline" class="no-data-icon"></ion-icon>
                <h4>No notes yet</h4>
                <p>Create your first note to get started with organizing your thoughts and ideas.</p>
              </div>
            </div>
            
            <div v-else class="notes-grid">
              <div v-for="(note, index) in notes" :key="index" class="note-card">
                <div class="note-actions">
                  <button class="note-action-btn edit" @click="editNote(note)" title="Edit note">
                    <ion-icon name="create-outline"></ion-icon>
                  </button>
                  <button class="note-action-btn delete" @click="confirmDeleteNote(note.id)" title="Delete note">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
                
                <div v-if="note.image" class="note-image-container">
                  <img :src="note.image" class="note-image" alt="Note image" />
                </div>
                
                <div class="note-content-area">
                  <h4 v-if="note.header" class="note-title">{{ note.header }}</h4>
                  <div v-if="note.content" class="note-preview" v-html="note.content"></div>
                  <div v-if="!note.header && !note.content" class="empty-note">
                    <span>Empty note</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Alert -->
        <ion-alert
          :is-open="showDeleteAlert"
          header="Confirm Delete"
          message="Are you sure you want to delete this note? This action cannot be undone."
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

      scrollToForm() {
        const formElement = document.querySelector('.note-form-card');
        if (formElement) {
          formElement.scrollIntoView({ behavior: 'smooth' });
        }
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
  /* Mobile-First Design System */
  .modern-content {
    --primary-color: #2563eb;
    --success-color: #059669;
    --danger-color: #dc2626;
    --background: #f8fafc;
    --surface: #ffffff;
    --border: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --radius: 12px;
    background: var(--background);
  }

  .page-container {
    padding: 16px;
    min-height: 100vh;
  }

  /* Page Header */
  .page-header {
    margin-bottom: 20px;
  }

  .header-content {
    margin-bottom: 16px;
  }

  .header-info {
    flex: 1;
  }

  .page-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 8px 0;
    color: var(--text-primary);
    font-size: 24px;
    font-weight: 700;
  }

  .page-title ion-icon {
    font-size: 28px;
    color: var(--primary-color);
  }

  .page-subtitle {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
  }

  /* Action Bar */
  .action-bar {
    margin-bottom: 20px;
  }

  .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 16px;
    border: none;
    border-radius: var(--radius);
    font-weight: 500;
    font-size: 14px;
    background: var(--surface);
    color: var(--text-primary);
    border: 1px solid var(--border);
    -webkit-tap-highlight-color: transparent;
    min-height: 44px;
  }

  .action-btn.primary {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }

  .action-btn ion-icon {
    font-size: 18px;
  }

  /* Data Card */
  .data-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 32px;
  }

  .card-header {
    padding: 24px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, var(--background), var(--surface));
  }

  .header-left h3 {
    margin: 0 0 4px 0;
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 600;
  }

  .form-description {
    color: var(--text-secondary);
    font-size: 14px;
  }

  .card-content {
    padding: 24px;
  }

  /* Modern Form */
  .modern-form {
    width: 100%;
  }

  .form-group {
    margin-bottom: 24px;
  }

  .form-label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-primary);
    font-weight: 500;
    font-size: 14px;
  }

  .modern-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 16px;
    background: var(--surface);
    color: var(--text-primary);
    box-sizing: border-box;
    min-height: 44px;
  }

  .modern-input:focus {
    outline: none;
    border-color: var(--primary-color);
  }

  .editor-group {
    margin-bottom: 32px;
  }

  .editor-wrapper {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--surface);
  }

  .modern-editor :deep(.ql-toolbar) {
    background: var(--background);
    border: none;
    border-bottom: 1px solid var(--border);
  }

  .modern-editor :deep(.ql-container) {
    border: none;
    min-height: 200px;
    font-size: 14px;
  }

  .modern-editor :deep(.ql-editor) {
    min-height: 200px;
    padding: 16px;
    line-height: 1.6;
  }

  .modern-editor :deep(.ql-editor.ql-blank::before) {
    color: var(--text-muted);
    font-style: normal;
  }

  .form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
  }

  /* Notes Section */
  .notes-section {
    margin-top: 48px;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }

  .section-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 20px;
    font-weight: 600;
  }

  .notes-count {
    color: var(--text-secondary);
    font-size: 14px;
  }

  /* No Data State */
  .no-data-state {
    padding: 60px 20px;
    text-align: center;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
  }

  .no-data-content {
    max-width: 400px;
    margin: 0 auto;
  }

  .no-data-icon {
    font-size: 64px;
    color: var(--text-muted);
    margin-bottom: 16px;
    opacity: 0.5;
  }

  .no-data-content h4 {
    margin: 0 0 8px 0;
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 600;
  }

  .no-data-content p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
    line-height: 1.5;
  }

  /* Notes Grid */
  .notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
  }

  .note-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    position: relative;
  }

  .note-actions {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
    z-index: 2;
  }

  .note-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: var(--radius);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    border: 1px solid var(--border);
    -webkit-tap-highlight-color: transparent;
  }

  .note-action-btn.edit {
    color: var(--primary-color);
  }

  .note-action-btn.delete {
    color: var(--danger-color);
  }

  .note-image-container {
    width: 100%;
    max-height: 200px;
    overflow: hidden;
  }

  .note-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .note-content-area {
    padding: 20px;
  }

  .note-title {
    margin: 0 0 12px 0;
    color: var(--text-primary);
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
  }

  .note-preview {
    color: var(--text-secondary);
    font-size: 14px;
    line-height: 1.5;
    max-height: 120px;
    overflow: hidden;
    position: relative;
  }

  .note-preview::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(transparent, var(--surface));
  }

  /* Clean up Quill content */
  .note-preview :deep(h1),
  .note-preview :deep(h2),
  .note-preview :deep(h3),
  .note-preview :deep(p) {
    margin: 0 0 8px 0;
    font-size: inherit;
    line-height: inherit;
  }

  .note-preview :deep(ul),
  .note-preview :deep(ol) {
    margin: 0 0 8px 0;
    padding-left: 20px;
  }

  .note-preview :deep(li) {
    margin: 0 0 4px 0;
  }

  .empty-note {
    color: var(--text-muted);
    font-style: italic;
    font-size: 14px;
  }

  </style>