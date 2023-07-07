<template>
  <quill-editor
    class="editor"
    :modules="modules"
    theme="snow"
    v-model="content"
    :value="content"
    @ionInput="alert(1)"
    @change="alert(1)"
    ref="qeditor"
    id="editor"
  />
  <QuillEditor
    v-model="content"
    :value="content"
    @ionInput="content = $event.target.value"
    ref="myQuillEditor"
    :options="editorOption"
  />
</template>

<script>
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import { ImageDrop } from "quill-image-drop-module";
import MagicUrl from "quill-magic-url";
import htmlEditButton from "quill-html-edit-button";
import BlotFormatter from "quill-blot-formatter";

export default {
  name: "HtmlInput",
  components: {
    QuillEditor,
  },
  data: () => ({
    title: "",
    category: "",
    image_url: "",
    content: "",
    editorOption: {
      debug: "info",
      placeholder: "Type your post...",
      // readOnly: true,
      theme: "snow",
    },
    delta: undefined,
  }),
  setup: () => {
    const modules = [
      {
        name: "ImageDrop",
        module: ImageDrop,
      },
      {
        name: "MagicUrl",
        module: MagicUrl,
      },
      {
        name: "htmlEditButton",
        module: htmlEditButton,
      },
      {
        name: "BlotFormatter",
        module: BlotFormatter,
      },
    ];

    return { modules };
  },
  mounted() {
    setInterval(() => {
      // console.log(this.$refs.myQuillEditor.quill.getContents());
    }, 200);
  },
  watch: {
    content() {
      this.delta = this.$refs.myQuillEditor.quill.getContents();
      alert(this.delta);
      alert(this.delta.length);
    },
  },
};
</script>

<style>
.editor,
.ql-container {
  height: 400px;
  border-color: red !important;
}

.ql-toolbar {
  border-color: red !important;
  color: red !important;
}

.ql-formats > button,
.ql-formats > button > svg > * {
  color: red !important;
  stroke: red !important;
}

.ql-editor {
  background-color: black;
}

.ql-snow .ql-tooltip input[type="text"] {
  color: white !important;
}
</style>
