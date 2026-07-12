<template>
  <div class="file-upload">
    <label class="file-upload-label">{{ label }}</label>

    <div class="file-upload-body">
      <div v-if="previewUrl && isImage" class="file-preview">
        <img :src="previewUrl" class="file-preview-image" @error="previewUrl = ''" />
      </div>
      <div v-else-if="modelValue" class="file-chip">
        <ion-icon name="document-outline"></ion-icon>
        <span class="file-chip-name">{{ fileName || modelValue }}</span>
      </div>

      <div class="file-upload-actions">
        <button type="button" class="file-btn" :disabled="uploading" @click="pick">
          <ion-icon :name="uploading ? 'cloud-upload-outline' : 'add-outline'"></ion-icon>
          <span>{{ uploading ? 'Uploading...' : (modelValue ? 'Replace' : 'Choose file') }}</span>
        </button>
        <button v-if="modelValue && !uploading" type="button" class="file-btn danger" @click="clear">
          <ion-icon name="close-outline"></ion-icon>
        </button>
      </div>

      <input ref="fileInput" type="file" class="file-input" @change="onFileChange" />
    </div>
  </div>
</template>

<script>
export default {
  name: "FloatingFileUpload",
  props: {
    modelValue: { type: String, default: "" },
    label: { type: String, default: "" },
    project: { type: String, required: true },
    projectID: { type: [String, Number], default: null },
  },
  emits: ["update:modelValue"],
  data() {
    return {
      uploading: false,
      previewUrl: "",
      fileName: "",
      resolvedProjectID: this.projectID,
    };
  },
  computed: {
    isImage() {
      return /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(this.modelValue || "");
    },
  },
  watch: {
    modelValue(value) {
      if (value && this.isImage) {
        this.loadPreview();
      } else {
        this.previewUrl = "";
      }
    },
  },
  mounted() {
    if (this.modelValue && this.isImage) {
      this.loadPreview();
    }
  },
  methods: {
    pick() {
      this.$refs.fileInput.click();
    },
    clear() {
      this.previewUrl = "";
      this.fileName = "";
      this.$emit("update:modelValue", "");
    },
    onFileChange(event) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      this.fileName = file.name;

      const reader = new FileReader();
      reader.onload = () => {
        const base64 = String(reader.result).split(",")[1];
        this.upload(file.name, base64);
      };
      reader.readAsDataURL(file);
      event.target.value = "";
    },
    async upload(name, base64) {
      this.uploading = true;
      try {
        const res = await this.$axios.post("filesystem_upload.php", {
          action: "upload_file",
          name,
          content: base64,
          isBase64: true,
          project: this.project,
          directory: "",
        });

        if (res.data && res.data.success) {
          this.resolvedProjectID = res.data.projectID;
          this.$emit("update:modelValue", res.data.path);
        } else {
          console.error("Upload failed:", res.data && res.data.message);
        }
      } catch (error) {
        console.error("Upload error:", error);
      } finally {
        this.uploading = false;
      }
    },
    async loadPreview() {
      if (!this.modelValue) return;
      try {
        const res = await this.$axios.post("signed_url_generator.php", {
          path: this.modelValue,
          projectID: this.resolvedProjectID,
          validitySeconds: 3600,
        });
        if (res.data && res.data.success) {
          this.previewUrl = res.data.url;
        }
      } catch (error) {
        console.error("Preview error:", error);
      }
    },
  },
};
</script>

<style scoped>
.file-upload {
  margin-bottom: 4px;
}

.file-upload-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary, #1e293b);
}

.file-upload-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.file-preview {
  width: 96px;
  height: 96px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #fff);
}

.file-preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.file-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  max-width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  background: var(--surface-alt, #f1f5f9);
  color: var(--text-secondary, #64748b);
  font-size: 13px;
}

.file-chip ion-icon {
  font-size: 18px;
}

.file-chip-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-upload-actions {
  display: flex;
  gap: 8px;
}

.file-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 14px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
  background: var(--surface, #fff);
  color: var(--primary-color, #f97316);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.file-btn:hover:not(:disabled) {
  border-color: var(--primary-color, #f97316);
  background: rgba(249, 115, 22, 0.06);
}

.file-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.file-btn.danger {
  color: var(--danger-color, #ef4444);
}

.file-btn.danger:hover {
  border-color: var(--danger-color, #ef4444);
  background: rgba(239, 68, 68, 0.06);
}

.file-btn ion-icon {
  font-size: 18px;
}

.file-input {
  display: none;
}
</style>
