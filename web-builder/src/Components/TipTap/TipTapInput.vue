<script setup>
import { Editor, useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { computed, onBeforeMount, onMounted, ref, watch, nextTick } from 'vue';
import PageBuilder from '@/composables/PageBuilder';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import DynamicModal from '@/Components/Modals/DynamicModal.vue';
import DynamicContentInserter from '@/Components/PageBuilder/DynamicContent/DynamicContentInserter.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useMediaLibraryStore } from '@/stores/media-library';
import tailwindColors from '@/utils/builder/tailwaind-colors';
import {
  hasDynamicContent,
  hasDynamicContentSyntax,
  hasDynamicContentBadges,
  convertDynamicContentToBadges,
  convertBadgesToDynamicContent,
} from '@/utils/builder/dynamic-content-parser';

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const showModalUrl = ref(false);
const showDynamicContentModal = ref(false);
const typeModal = ref('');
const gridColumnModal = ref(Number(1));
const titleModal = ref('');
const descriptionModal = ref('');
const firstButtonModal = ref('');
const secondButtonModal = ref(null);
const thirdButtonModal = ref(null);
const firstModalButtonFunction = ref(null);
const secondModalButtonFunction = ref(null);
const thirdModalButtonFunction = ref(null);
const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

const getElement = computed(() => {
  return pageBuilderStateStore.getElement;
});

const getSelectedTextElement = computed(() => {
  return pageBuilderStateStore.getSelectedTextElement;
});

const initialContent = ref('');
const linkUnderlineEnabled = ref(true);

const prepareEditorContent = () => {
  let content = '';
  if (getSelectedTextElement.value) {
    content = getSelectedTextElement.value.innerHTML || '';
  } else if (getElement.value) {
    content = getElement.value.innerHTML || '';
  }

  if (content && hasDynamicContentBadges(content)) {
    content = convertBadgesToDynamicContent(content);
  }

  initialContent.value = content;
  return initialContent.value;
};

const checkLinkUnderlineStatus = () => {
  const element = getSelectedTextElement.value || getElement.value;
  if (!element) return;

  const links = element.querySelectorAll('a');
  if (links.length > 0) {

    const firstLink = links[0];
    const hasUnderlineClass = firstLink.classList.contains('underline');
    const hasTextDecorationStyle = window.getComputedStyle(firstLink).textDecoration.includes('underline');
    linkUnderlineEnabled.value = hasUnderlineClass || hasTextDecorationStyle;
  }
};

prepareEditorContent();

const textContent = computed(() => {
  if (editor.value) {
    return editor.value.getHTML();
  }
  return '';
});

const getElementtextContentLength = ref(0);

watch(getElement, (newVal) => {
  const tempContainer = document.createElement('div');

  if (newVal) {
    tempContainer.innerHTML = newVal;
    const textContent = tempContainer.textContent;
    getElementtextContentLength.value = textContent.length;
  }
});

const linkExtension = Link.configure({
  openOnClick: false,
  HTMLAttributes: {
    class: linkUnderlineEnabled.value ? 'text-indigo-600 underline cursor-pointer' : 'text-indigo-600 no-underline cursor-pointer',
  },

  parseHTML() {
    return [
      {
        tag: 'a[href]',
        getAttrs: el => ({
          href: el.getAttribute('href'),
          target: el.getAttribute('target'),
          rel: el.getAttribute('rel'),
          class: el.getAttribute('class')
        })
      }
    ]
  }
});

const editor = useEditor({
  content: initialContent.value,
  extensions: [
    StarterKit,
    linkExtension,
    Underline,
  ],
  editorProps: {
    attributes: {
      class: 'prose-sm sm:prose-sm lg:prose-sm focus:outline-none',
    },
  },
});

const toggleLinkUnderline = () => {
  if (!editor.value) return;

  linkUnderlineEnabled.value = !linkUnderlineEnabled.value;

  const editorElement = document.querySelector('#page-builder-editor');
  if (!editorElement) return;

  const links = editorElement.querySelectorAll('a');

  links.forEach(link => {
    if (linkUnderlineEnabled.value) {
      link.classList.add('underline');
      link.classList.remove('no-underline');
    } else {
      link.classList.remove('underline');
      link.classList.add('no-underline');
    }
  });

  editor.value.extensionManager.extensions.forEach(extension => {
    if (extension.name === 'link') {
      extension.options.HTMLAttributes.class = linkUnderlineEnabled.value
        ? 'text-indigo-600 underline cursor-pointer'
        : 'text-indigo-600 no-underline cursor-pointer';
    }
  });
};

const getContentFromActiveElement = () => {
  let content = '';

  if (getSelectedTextElement.value) {
    content = getSelectedTextElement.value.innerHTML;
  } else if (getElement.value) {
    content = getElement.value.innerHTML;
  }

  if (content && hasDynamicContentBadges(content)) {
    content = convertBadgesToDynamicContent(content);
  }

  return content;
};

const TipTapSetContent = function () {
  if (editor.value) {
    const contentToEdit = getContentFromActiveElement();
    if (contentToEdit) {
      const originalElement = getSelectedTextElement.value;

      if (originalElement && originalElement.tagName.toLowerCase() === 'a') {
        editor.value.commands.setContent(contentToEdit, false);

        if (originalElement.hasAttribute('href')) {
          const href = originalElement.getAttribute('href');
          editor.value.chain().selectAll().setLink({ href }).run();
        }
      } else {
        editor.value.commands.setContent(contentToEdit);
      }
    }

    nextTick(() => {
      checkLinkUnderlineStatus();
    });
  }
};

watch([getElement, getSelectedTextElement], () => {

  if (editor.value) {
    nextTick(() => {
      TipTapSetContent();
    });
  }
});

const urlEnteret = ref('');
const newUpdatedExistingURL = ref('');
const urlError = ref(null);

watch(urlEnteret, (newVal) => {
  newUpdatedExistingURL.value = newVal;
});

const handleURL = function () {
  if (!editor.value) return;

  urlEnteret.value = editor.value.getAttributes('link').href;
  showModalUrl.value = true;
  typeModal.value = 'success';
  gridColumnModal.value = 2;
  titleModal.value = 'Enter URL';
  descriptionModal.value = null;
  firstButtonModal.value = 'Close';
  secondButtonModal.value = urlEnteret.value ? 'Remove url' : null;
  thirdButtonModal.value = 'Save';

  firstModalButtonFunction.value = function () {
    showModalUrl.value = false;
    urlError.value = null;
  };

  secondModalButtonFunction.value = function () {
    if (editor.value) {
      editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    }
    showModalUrl.value = false;
  };

  thirdModalButtonFunction.value = function () {
    const isNotValidated = validateURL();
    if (isNotValidated) {
      return;
    }
    if (!isNotValidated && editor.value) {
      setEnteretURL();
    }
    showModalUrl.value = false;
  };

};

const validateURL = function () {
  urlError.value = null;
  const urlRegex = /^https?:\/\//;
  const isValidURL = ref(true);
  isValidURL.value = urlRegex.test(newUpdatedExistingURL.value);

  if (isValidURL.value === false) {
    urlError.value =
      "The provided URL is invalid. Please ensure that it begins with 'https://' for proper formatting and security.";
    return true;
  }

  return false;
};

const setEnteretURL = function () {
  if (editor.value) {
    editor.value
      .chain()
      .focus()
      .extendMarkRange('link')
      .setLink({
        href: newUpdatedExistingURL.value,
        class: linkUnderlineEnabled.value ? 'text-indigo-600 underline cursor-pointer' : 'text-indigo-600 no-underline cursor-pointer'
      })
      .run();
  }
};

onBeforeMount(() => {
  if (editor.value) {
    editor.value?.destroy();
  }
});

onMounted(() => {
  if (editor.value) {
    nextTick(() => {
      TipTapSetContent();
    });
  }
});

const syncElementToComponent = (element) => {
  if (!element) return;

  const section = element.closest('section[data-componentid]');
  if (!section) return;

  const componentId = section.dataset.componentid;
  if (!componentId) return;

  const components = pageBuilderStateStore.getComponents;
  if (!components || !Array.isArray(components)) return;

  const componentIndex = components.findIndex(c => c.id === componentId);
  if (componentIndex === -1) return;

  components[componentIndex].html_code = section.outerHTML;
  pageBuilderStateStore.setComponents([...components]);
};

const handleTextSave = async () => {
  if (!editor.value) return;

  let newContent = editor.value.getHTML();

  try {
    if (getSelectedTextElement.value) {
      const element = getSelectedTextElement.value;

      const originalTagName = element.tagName.toLowerCase();

      if (originalTagName === 'a') {

        const tempContainer = document.createElement('div');
        tempContainer.innerHTML = newContent;

        const editorLink = tempContainer.querySelector('a');
        if (editorLink) {

          element.innerHTML = editorLink.innerHTML;

          if (editorLink.hasAttribute('href')) {
            element.setAttribute('href', editorLink.getAttribute('href'));
          }
          if (editorLink.hasAttribute('target')) {
            element.setAttribute('target', editorLink.getAttribute('target'));
          }
          if (editorLink.hasAttribute('rel')) {
            element.setAttribute('rel', editorLink.getAttribute('rel'));
          }

          if (editorLink.hasAttribute('class')) {

            const existingClasses = element.className.split(' ').filter(cls =>
              !['underline', 'no-underline'].includes(cls)
            );

            const editorClasses = editorLink.className.split(' ');
            element.className = [...existingClasses, ...editorClasses].join(' ');
          }

          if (!linkUnderlineEnabled.value) {
            element.classList.remove('underline');
            element.classList.add('no-underline');
          } else {
            element.classList.add('underline');
            element.classList.remove('no-underline');
          }

        } else {
          let content = tempContainer.innerHTML;
          const pStart = content.indexOf('<p>');
          const pEnd = content.lastIndexOf('</p>');

          if (pStart === 0 && pEnd > 0) {
            content = content.substring(3, pEnd);
          }

          if (hasDynamicContentSyntax(content)) {
            const contentData = pageBuilderStateStore.contentTables ?
              pageBuilderStateStore.contentTables.reduce((acc, table) => {
                acc[table.name] = { data: table.data || [], columns: table.columns || [] };
                return acc;
              }, {}) : null;
            content = convertDynamicContentToBadges(content, contentData);
          }

          element.innerHTML = content;
        }

        syncElementToComponent(element);

        pageBuilderStateStore.setShowModalTipTap(false);
        return;
      }

      const tempContainer = document.createElement('div');
      tempContainer.innerHTML = newContent;

      if (originalTagName === 'p') {
        const nestedParagraphs = tempContainer.querySelectorAll('p p');

        if (nestedParagraphs.length > 0 || tempContainer.querySelector(':scope > p')) {
          let contentToKeep = '';
          const firstLevelP = tempContainer.querySelector(':scope > p');

          if (firstLevelP) {
            contentToKeep = firstLevelP.innerHTML;
          } else {
            contentToKeep = tempContainer.innerHTML;
          }

          tempContainer.innerHTML = contentToKeep;
        }
      }

      else if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span'].includes(originalTagName)) {
        const paragraphs = tempContainer.querySelectorAll(':scope > p');

        if (paragraphs.length > 0) {

          let extractedContent = '';
          paragraphs.forEach(p => {
            extractedContent += p.innerHTML;
          });

          tempContainer.innerHTML = extractedContent;
        }
      }

      const structuralTags = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'section', 'article'];
      const hasNestedStructuralTags = structuralTags.some(tag => {

        if (tag !== originalTagName) {
          return tempContainer.querySelector(`${originalTagName} ${tag}`) !== null;
        }
        return false;
      });

      if (hasNestedStructuralTags) {
        structuralTags.forEach(tag => {
          if (tag === originalTagName) return;
          const nestedElements = tempContainer.querySelectorAll(tag);
          nestedElements.forEach(nestedEl => {
            const fragment = document.createDocumentFragment();

            while (nestedEl.firstChild) {
              fragment.appendChild(nestedEl.firstChild);
            }

            if (nestedEl.parentNode) {
              nestedEl.parentNode.replaceChild(fragment, nestedEl);
            }
          });
        });
      }

      newContent = tempContainer.innerHTML;

      if (hasDynamicContentSyntax(newContent)) {
        const contentData = pageBuilderStateStore.contentTables ?
          pageBuilderStateStore.contentTables.reduce((acc, table) => {
            acc[table.name] = { data: table.data || [], columns: table.columns || [] };
            return acc;
          }, {}) : null;
        newContent = convertDynamicContentToBadges(newContent, contentData);
      }

      element.innerHTML = newContent;

      syncElementToComponent(element);

      pageBuilderStateStore.setShowModalTipTap(false);
    } else if (pageBuilder.selectedElementIsValidText()) {

      const tempContainer = document.createElement('div');
      tempContainer.innerHTML = newContent;

      const paragraphs = tempContainer.querySelectorAll(':scope > p');
      if (paragraphs.length > 0) {
        let extractedContent = '';
        paragraphs.forEach(p => {
          extractedContent += p.innerHTML;
        });
        newContent = extractedContent;
      }

      if (hasDynamicContentSyntax(newContent)) {
        const contentData = pageBuilderStateStore.contentTables ?
          pageBuilderStateStore.contentTables.reduce((acc, table) => {
            acc[table.name] = { data: table.data || [], columns: table.columns || [] };
            return acc;
          }, {}) : null;
        newContent = convertDynamicContentToBadges(newContent, contentData);
      }

      pageBuilder.handleTextInput(newContent);
      pageBuilderStateStore.setShowModalTipTap(false);
    }
  } catch (error) {
    console.error('Fehler beim Speichern des Texts:', error);
  }
};

watch(() => pageBuilderStateStore.getShowModalTipTap, (newValue, oldValue) => {
  if (oldValue === true && newValue === false) {

    pageBuilder.clearTextElementSelection();
  }
});

const showTextColorDropdown = ref(false);

const selectedTextColor = ref('none');

const textColorOptions = computed(() => {

  return [
    { name: 'Schwarz', value: 'text-black' },
    { name: 'Weiß', value: 'text-white' },
    { name: 'Grau 400', value: 'text-gray-400' },
    { name: 'Grau 500', value: 'text-gray-500' },
    { name: 'Grau 600', value: 'text-gray-600' },
    { name: 'Grau 700', value: 'text-gray-700' },
    { name: 'Grau 800', value: 'text-gray-800' },
    { name: 'Rot 500', value: 'text-red-500' },
    { name: 'Rot 600', value: 'text-red-600' },
    { name: 'Rot 700', value: 'text-red-700' },
    { name: 'Orange 500', value: 'text-orange-500' },
    { name: 'Orange 600', value: 'text-orange-600' },
    { name: 'Gelb 500', value: 'text-yellow-500' },
    { name: 'Gelb 600', value: 'text-yellow-600' },
    { name: 'Grün 500', value: 'text-green-500' },
    { name: 'Grün 600', value: 'text-green-600' },
    { name: 'Blau 500', value: 'text-blue-500' },
    { name: 'Blau 600', value: 'text-blue-600' },
    { name: 'Indigo 500', value: 'text-indigo-500' },
    { name: 'Indigo 600', value: 'text-indigo-600' },
    { name: 'Lila 500', value: 'text-purple-500' },
    { name: 'Lila 600', value: 'text-purple-600' },
    { name: 'Pink 500', value: 'text-pink-500' },
    { name: 'Pink 600', value: 'text-pink-600' },
    { name: 'Keine Farbe', value: 'none' }
  ];
});

const getCurrentTextColorFromElement = () => {
  if (getSelectedTextElement.value) {
    const element = getSelectedTextElement.value;

    for (const colorClass of tailwindColors.textColorVariables) {
      if (colorClass !== 'none' && element.classList.contains(colorClass)) {
        selectedTextColor.value = colorClass;
        return;
      }
    }
  }
  selectedTextColor.value = 'none';
};

const applyTextColor = (newColor) => {
  if (!getSelectedTextElement.value) return;

  const element = getSelectedTextElement.value;

  tailwindColors.textColorVariables.forEach(colorClass => {
    if (colorClass !== 'none' && element.classList.contains(colorClass)) {
      element.classList.remove(colorClass);
    }
  });

  if (newColor !== 'none') {
    element.classList.add(newColor);
  }

  selectedTextColor.value = newColor;

  showTextColorDropdown.value = false;
};

watch(() => pageBuilderStateStore.getShowModalTipTap, (newValue) => {
  if (newValue === true) {

    nextTick(() => {
      getCurrentTextColorFromElement();
    });
  }
});

const openDynamicContentModal = () => {
  showDynamicContentModal.value = true;
};

const closeDynamicContentModal = () => {
  showDynamicContentModal.value = false;
};

const insertDynamicContent = (syntax) => {
  if (!editor.value) return;
  editor.value.chain().focus().insertContent(syntax).run();
  closeDynamicContentModal();
};
</script>
<template>
  <DynamicModal :show="showModalUrl" :type="typeModal" :gridColumnAmount="gridColumnModal" :title="titleModal"
    :description="descriptionModal" :firstButtonText="firstButtonModal" :secondButtonText="secondButtonModal"
    :thirdButtonText="thirdButtonModal" @firstModalButtonFunction="firstModalButtonFunction"
    @secondModalButtonFunction="secondModalButtonFunction" @thirdModalButtonFunction="thirdModalButtonFunction">
    <header></header>
    <main>
      <div class="myInputGroup">
        <label class="myPrimaryInputLabel" for="roles"><span>Enter URL</span></label><input v-model="urlEnteret"
          class="myPrimaryInput mt-1" type="url" placeholder="url" />
        <div v-if="urlError" class="min-h-[2.5rem] flex items-center justify-start">
          <p class="myPrimaryInputError mt-2 mb-0 py-0 self-start">
            {{ urlError }}
          </p>
        </div>
      </div>
    </main>
  </DynamicModal>

  <div class="blockease-linear duration-200 block ease-linear">
    <div v-if="(pageBuilder.selectedElementIsValidText() || getSelectedTextElement) && editor">
      <div class="relative rounded-lg">
        <div
          class="flex justify-between myPrimaryGap items-center divide-x divide-gray-200 py-4 px-4 overflow-x-auto border-b border-gray-20">
          <div class="flex items-center 0 divide-x divide-gray-200">
            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="editor.chain().focus().setHardBreak().run()" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
                <span class="material-symbols-outlined"> keyboard_return </span>
                <span>Line break</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="editor.chain().focus().toggleBold().run()" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('bold'),
                }">
                <span class="material-symbols-outlined"> format_bold </span>
                <span>Bold</span>
              </button>
            </div>

            <!-- Neue Schaltfläche für allgemeine Unterstreichung -->
            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="editor.chain().focus().toggleUnderline().run()" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('underline'),
                }">
                <span class="material-symbols-outlined"> format_underlined </span>
                <span>Unterstreichen</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="handleURL" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive('link'),
                }">
                <span class="material-symbols-outlined"> link </span>
                <span>Link</span>
              </button>
            </div>

            <!-- Link-Unterstreichungs-Button nur anzeigen wenn ein Link aktiv ist -->
            <div class="px-2 flex items-center justify-start gap-2" v-if="editor.isActive('link')">
              <button @click="toggleLinkUnderline" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': linkUnderlineEnabled,
                }">
                <span class="material-symbols-outlined"> format_underlined </span>
                <span>Link unterstreichen</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="
                editor.chain().focus().toggleHeading({ level: 2 }).run()
                " type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive(
                    'heading',
                    {
                      level: 2,
                    }
                  ),
                }">
                <span class="material-symbols-outlined"> titlecase </span>
                <span>Header 2</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="
                editor.chain().focus().toggleHeading({ level: 3 }).run()
                " type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white': editor.isActive(
                    'heading',
                    {
                      level: 3,
                    }
                  ),
                }">
                <span class="material-symbols-outlined"> titlecase </span>
                <span>Header 3</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="editor.chain().focus().toggleBulletList().run()" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                :class="{
                  'bg-myPrimaryLinkColor text-white':
                    editor.isActive('bulletList'),
                }">
                <span class="material-symbols-outlined"> list </span>
                <span>List</span>
              </button>
            </div>

            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="showTextColorDropdown = !showTextColorDropdown" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
                <span class="material-symbols-outlined"> palette </span>
                <span>Textfarbe</span>
              </button>
            </div>

            <!-- Dynamic Content Button -->
            <div class="px-2 flex items-center justify-start gap-2">
              <button @click="openDynamicContentModal" type="button"
                class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gradient-to-r from-emerald-500 to-teal-500 text-white hover:from-emerald-600 hover:to-teal-600 focus-visible:ring-0">
                <span class="material-symbols-outlined"> database </span>
                <span>Dynamic Content</span>
              </button>
            </div>
          </div>
          <div>
            <div>
              <div class="px-2 flex items-center justify-start gap-2">
                <button @click="handleTextSave" type="button"
                  class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2">
                  Speichern und schließen
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="showTextColorDropdown"
          class="absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg mt-2">
          <ul class="py-2">
            <li v-for="option in textColorOptions" :key="option.value" @click="applyTextColor(option.value)"
              class="px-4 py-2 cursor-pointer hover:bg-gray-100"
              :class="{ 'font-bold': selectedTextColor === option.value }">
              {{ option.name }}
            </li>
          </ul>
        </div>

        <editor-content v-if="editor" id="page-builder-editor" :editor="editor"
          class="px-4 pt-6 pb-12 bg-white rounded-lg lg:min-h-[20rem] lg:max-h-[30rem] md:min-h-[20rem] md:max-h-[20rem] min-h-[20rem] max-h-[20rem] overflow-y-auto" />
      </div>
    </div>
  </div>

  <!-- Dynamic Content Inserter Modal -->
  <DynamicContentInserter :show="showDynamicContentModal" @close="closeDynamicContentModal"
    @insert="insertDynamicContent" />
</template>
