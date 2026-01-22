<template>
    <div class="wb-page-view">
        <div class="wb-header">
            <div class="header-left">
                <button class="back-btn" @click="goBack">
                    <ion-icon name="arrow-back-outline"></ion-icon>
                </button>
                <h1 class="page-title">{{ pageData?.name || 'Loading...' }}</h1>
                <span class="page-slug">/{{ pageData?.slug }}</span>
            </div>
            <div class="header-right">
                <button class="view-toggle-btn" :class="{ active: viewMode === 'split' }" @click="viewMode = 'split'"
                    title="Split View">
                    <ion-icon name="browsers-outline"></ion-icon>
                </button>
                <button class="view-toggle-btn" :class="{ active: viewMode === 'code' }" @click="viewMode = 'code'"
                    title="Code Only">
                    <ion-icon name="code-outline"></ion-icon>
                </button>
                <button class="view-toggle-btn" :class="{ active: viewMode === 'preview' }"
                    @click="viewMode = 'preview'" title="Preview Only">
                    <ion-icon name="eye-outline"></ion-icon>
                </button>
                <button class="save-btn" @click="savePage" :disabled="!hasChanges || isSaving">
                    <ion-icon :name="isSaving ? 'hourglass-outline' : 'save-outline'"></ion-icon>
                    {{ isSaving ? 'Saving...' : 'Save' }}
                </button>
                <button class="publish-btn" @click="publishPage">
                    <ion-icon name="cloud-upload-outline"></ion-icon>
                    Publish
                </button>
            </div>
        </div>
        <div class="wb-content" :class="'view-' + viewMode">
            <div v-show="viewMode !== 'preview'" class="editor-panel">
                <div class="editor-tabs">
                    <button v-for="tab in editorTabs" :key="tab.id" class="editor-tab"
                        :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
                        <ion-icon :name="tab.icon"></ion-icon>
                        {{ tab.label }}
                    </button>
                </div>
                <div class="editor-container">
                    <vue-monaco-editor v-model:value="currentCode" :language="currentLanguage" theme="vs-dark"
                        :options="editorOptions" width="100%" height="100%" @change="onCodeChange" />
                </div>
            </div>
            <div v-show="viewMode !== 'code'" class="preview-panel">
                <div class="preview-header">
                    <div class="preview-device-selector">
                        <button v-for="device in devices" :key="device.name" class="device-btn"
                            :class="{ active: selectedDevice === device.name }" @click="selectedDevice = device.name"
                            :title="device.name">
                            <ion-icon :name="device.icon"></ion-icon>
                        </button>
                    </div>

                    <button class="refresh-preview-btn" @click="refreshPreview" title="Refresh Preview">
                        <ion-icon name="refresh-outline"></ion-icon>
                    </button>
                </div>

                <div class="preview-viewport" :class="'device-' + selectedDevice">
                    <iframe ref="previewFrame" :srcdoc="previewHtml" class="preview-iframe"
                        sandbox="allow-scripts allow-same-origin"></iframe>
                </div>
            </div>
        </div>
        <div class="ai-assistant-button" @click="toggleAssistant" :class="{ active: showAssistant }">
            <i class="ai-icon">AI</i>
        </div>
        <div v-if="showAssistant" class="ai-chat-modal">
            <div class="chat-header">
                <h3>AI Assistant</h3>
                <div class="chat-controls">
                    <button class="mode-toggle" @click="toggleAgentMode" :class="{ active: agentMode }"
                        :title="agentMode ? 'Agent Mode - AI kann Code bearbeiten' : 'Chat Mode - Nur Antworten'">
                        {{ agentMode ? '🔧' : '💬' }}
                    </button>
                    <button class="close-btn" @click="toggleAssistant">×</button>
                </div>
            </div>
            <div class="chat-messages" ref="chatMessages">
                <div v-for="(message, index) in chatHistory" :key="index" class="message" :class="message.type">
                    <div class="message-content" v-html="message.content"></div>
                    <div v-if="message.replacements && message.replacements.length > 0" class="code-replacements">
                        <div v-for="(replacement, rIndex) in message.replacements" :key="rIndex"
                            class="replacement-item">
                            <button @click="applyReplacement(replacement)" class="apply-btn">
                                Code ändern anwenden
                            </button>
                            <div class="replacement-preview">
                                <div class="old-code">
                                    <strong>Alt:</strong>
                                    <pre>{{ replacement.oldCode }}</pre>
                                </div>
                                <div class="new-code">
                                    <strong>Neu:</strong>
                                    <pre>{{ replacement.newCode }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="message-time">{{ message.time }}</div>
                </div>
                <div v-if="isTyping" class="message ai typing">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
            <div class="chat-input">
                <textarea v-model="userQuestion" @keydown.enter.prevent="handleEnterKey"
                    placeholder="Stelle eine Frage oder bitte um Code-Änderungen..." ref="chatInput"></textarea>
                <button @click="askAI" :disabled="!userQuestion.trim() || isTyping" class="send-btn">
                    {{ isTyping ? '⏳' : '➤' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ToastService } from '@/services/ToastService'
import { IonIcon } from '@ionic/vue'
import { marked } from 'marked'

const route = useRoute()
const router = useRouter()
const toast = ToastService

const projectId = route.params.project
const wbProjectId = route.params.wb_project
const pageSlug = route.params.page

const pageData = ref(null)
const pageComponents = ref([])
const hasChanges = ref(false)
const isSaving = ref(false)

const viewMode = ref('split')
const selectedDevice = ref('desktop')

const editorTabs = [
    { id: 'html', label: 'HTML', icon: 'code-slash-outline' },
    { id: 'css', label: 'CSS', icon: 'color-palette-outline' },
    { id: 'js', label: 'JavaScript', icon: 'logo-javascript' },
    { id: 'components', label: 'Components', icon: 'cube-outline' },
]

const activeTab = ref('html')
const htmlCode = ref('')
const cssCode = ref('')
const jsCode = ref('')
const componentsJson = ref('[]')

const currentCode = computed({
    get: () => {
        switch (activeTab.value) {
            case 'html': return htmlCode.value
            case 'css': return cssCode.value
            case 'js': return jsCode.value
            case 'components': return componentsJson.value
            default: return ''
        }
    },
    set: (value) => {
        switch (activeTab.value) {
            case 'html': htmlCode.value = value; break
            case 'css': cssCode.value = value; break
            case 'js': jsCode.value = value; break
            case 'components': componentsJson.value = value; break
        }
    }
})

const currentLanguage = computed(() => {
    switch (activeTab.value) {
        case 'html': return 'html'
        case 'css': return 'css'
        case 'js': return 'javascript'
        case 'components': return 'json'
        default: return 'html'
    }
})

const editorOptions = {
    fontSize: 14,
    minimap: { enabled: true },
    automaticLayout: true,
    formatOnType: true,
    formatOnPaste: true,
    scrollBeyondLastLine: false,
    wordWrap: 'on',
    theme: 'vs-dark',
}

const devices = [
    { name: 'desktop', icon: 'desktop-outline', width: '100%' },
    { name: 'tablet', icon: 'tablet-landscape-outline', width: '768px' },
    { name: 'mobile', icon: 'phone-portrait-outline', width: '375px' },
]

const previewHtml = ref('')
//const previewFrame = ref(null)

const showAssistant = ref(false)
const agentMode = ref(true)
const userQuestion = ref('')
const chatHistory = ref([])
const isTyping = ref(false)
const chatMessages = ref(null)
//const chatInput = ref(null)

const loadPageData = async () => {
    try {
        const response = await axios.get('web-builder/pages.php', {
            params: {
                project_id: wbProjectId,
                slug: pageSlug
            }
        })

        pageData.value = response.data

        await loadComponents()
        await generateSourceCode()
        updatePreview()
    } catch (error) {
        console.error('Error loading page:', error)
        toast.error('Fehler beim Laden der Seite')
    }
}

const loadComponents = async () => {
    try {
        const response = await axios.get('web-builder/components.php', {
            params: {
                page_id: pageData.value.id
            }
        })

        pageComponents.value = response.data
        componentsJson.value = JSON.stringify(pageComponents.value, null, 2)
    } catch (error) {
        console.error('Error loading components:', error)
    }
}

const generateSourceCode = async () => {
    if (!pageData.value) return

    try {
        await renderComponentsToHtml()
    } catch (error) {
        console.error('Error generating source code:', error)
        generateFallbackHtml()
    }
}

const renderComponentsToHtml = () => {
    if (!pageComponents.value || pageComponents.value.length === 0) {
        generateFallbackHtml()
        return
    }

    let combinedHtml = ''
    let combinedCss = ''
    let combinedJs = ''

    pageComponents.value.forEach(component => {
        const htmlContent = component.html_code || ''
        combinedHtml += htmlContent + '\n\n'
    })

    const styleMatches = combinedHtml.match(/<style[^>]*>([\s\S]*?)<\/style>/gi)
    if (styleMatches) {
        styleMatches.forEach(match => {
            const css = match.replace(/<\/?style[^>]*>/gi, '')
            combinedCss += css + '\n'
            combinedHtml = combinedHtml.replace(match, '')
        })
    }

    const scriptMatches = combinedHtml.match(/<script[^>]*>([\s\S]*?)<\/script>/gi)
    if (scriptMatches) {
        scriptMatches.forEach(match => {
            const js = match.replace(/<\/?script[^>]*>/gi, '')
            combinedJs += js + '\n'
            combinedHtml = combinedHtml.replace(match, '')
        })
    }

    cssCode.value = combinedCss.trim()
    jsCode.value = combinedJs.trim()
    htmlCode.value = combinedHtml.trim()
}

/*const parseGeneratedHtml = (fullHtml) => {
    const cssMatch = fullHtml.match(/<style[^>]*>([\s\S]*?)<\/style>/i)
    cssCode.value = cssMatch ? cssMatch[1].trim() : ''

    const jsMatch = fullHtml.match(/<script[^>]*>([\s\S]*?)<\/script>/i)
    jsCode.value = jsMatch ? jsMatch[1].trim() : ''

    const bodyMatch = fullHtml.match(/<body[^>]*>([\s\S]*?)<\/body>/i)
    htmlCode.value = bodyMatch ? bodyMatch[1].trim() : fullHtml
}*/

const generateFallbackHtml = () => {
    const title = pageData.value.title || pageData.value.name
    const pageName = pageData.value.name

    htmlCode.value = '<div class="container">\n' +
        '  <h1>' + title + '</h1>\n' +
        '  <p>Web Builder Page Content</p>\n' +
        '</div>'

    cssCode.value = '/* Styles for ' + pageName + ' */\n' +
        '.container {\n' +
        '  max-width: 1200px;\n' +
        '  margin: 0 auto;\n' +
        '  padding: 20px;\n' +
        '}\n\n' +
        'h1 {\n' +
        '  color: #333;\n' +
        '  font-size: 2rem;\n' +
        '  margin-bottom: 1rem;\n' +
        '}\n\n' +
        'p {\n' +
        '  color: #666;\n' +
        '  line-height: 1.6;\n' +
        '}'

    jsCode.value = '// JavaScript for ' + pageName + '\n' +
        'console.log(\'Page loaded: ' + pageName + '\');'
}

const updatePreview = () => {
    const title = pageData.value?.title || 'Preview'
    const scriptOpen = '<scr' + 'ipt>'
    const scriptClose = '</scr' + 'ipt>'
    previewHtml.value = '<!DOCTYPE html>\n' +
        '<html lang="de">\n' +
        '<head>\n' +
        '  <meta charset="UTF-8">\n' +
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n' +
        '  <title>' + title + '</title>\n' +
        '  <style>\n' +
        '    ' + cssCode.value + '\n' +
        '  </style>\n' +
        //'  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" />\n' +
        '</head>\n' +
        '<body>\n' +
        '  ' + htmlCode.value + '\n' +
        '  ' + scriptOpen + '\n' +
        '    ' + jsCode.value + '\n' +
        '  ' + scriptClose + '\n' +
        '</body>\n' +
        '</html>'
}

const refreshPreview = () => {
    updatePreview()
}

const onCodeChange = () => {
    hasChanges.value = true
    if (updatePreviewTimeout) clearTimeout(updatePreviewTimeout)
    updatePreviewTimeout = setTimeout(() => {
        updatePreview()
    }, 500)
}

let updatePreviewTimeout = null

const savePage = async () => {
    if (!hasChanges.value || isSaving.value) return

    isSaving.value = true

    try {
        if (activeTab.value === 'components') {
            const components = JSON.parse(componentsJson.value)

            await axios.post('/backend/web-builder/components.php', components, {
                params: { page_id: pageData.value.id }
            })
        } else {
            const updatedComponents = convertHtmlToComponents()

            await axios.post('/backend/web-builder/components.php', updatedComponents, {
                params: { page_id: pageData.value.id }
            })

            await loadComponents()
        }

        hasChanges.value = false
        toast.success('Seite gespeichert')
    } catch (error) {
        console.error('Error saving page:', error)
        toast.error('Fehler beim Speichern')
    } finally {
        isSaving.value = false
    }
}

const convertHtmlToComponents = () => {
    let fullHtml = htmlCode.value

    if (cssCode.value.trim()) {
        fullHtml = '<style>\n' + cssCode.value + '\n</style>\n' + fullHtml
    }

    if (jsCode.value.trim()) {
        fullHtml = fullHtml + '\n<scr' + 'ipt>\n' + jsCode.value + '\n</scr' + 'ipt>'
    }

    return [{
        id: pageComponents.value[0]?.id || null,
        html_code: fullHtml,
        position: 0
    }]
}

const publishPage = async () => {
    try {
        const response = await axios.post('web-builder/publish.php', {
            project_id: wbProjectId,
            page_id: pageData.value.id
        })

        toast.success('Seite veröffentlicht')
    } catch (error) {
        console.error('Error publishing page:', error)
        toast.error('Fehler beim Veröffentlichen')
    }
}

const goBack = () => {
    router.push(`/project/${projectId}/wb/${wbProjectId}`)
}

const toggleAssistant = () => {
    showAssistant.value = !showAssistant.value
}

const toggleAgentMode = () => {
    agentMode.value = !agentMode.value
    toast.info(agentMode.value ? 'Agent Mode aktiviert' : 'Chat Mode aktiviert')
}

const handleEnterKey = (event) => {
    if (!event.shiftKey && userQuestion.value.trim()) {
        askAI()
    }
}

const askAI = async () => {
    if (!userQuestion.value.trim() || isTyping.value) return

    const question = userQuestion.value
    userQuestion.value = ''

    chatHistory.value.push({
        type: 'user',
        content: question,
        time: new Date().toLocaleTimeString()
    })

    isTyping.value = true
    scrollChatToBottom()

    try {
        const context = {
            currentTab: activeTab.value,
            code: currentCode.value,
            pageData: pageData.value,
            components: pageComponents.value
        }

        const response = await axios.post('ai_assistant.php', {
            message: question,
            context: JSON.stringify(context),
            agent_mode: agentMode.value,
            codespace: `wb-${wbProjectId}-${pageSlug}`
        })

        const aiMessage = {
            type: 'ai',
            content: marked.parse(response.data.message || response.data.response || 'Keine Antwort'),
            time: new Date().toLocaleTimeString(),
            replacements: response.data.replacements || []
        }

        chatHistory.value.push(aiMessage)

        if (agentMode.value && aiMessage.replacements.length > 0) {
            for (const replacement of aiMessage.replacements) {
                await applyReplacement(replacement)
            }
        }

    } catch (error) {
        console.error('AI Error:', error)
        chatHistory.value.push({
            type: 'ai',
            content: 'Entschuldigung, es ist ein Fehler aufgetreten.',
            time: new Date().toLocaleTimeString()
        })
    } finally {
        isTyping.value = false
        scrollChatToBottom()
    }
}

const applyReplacement = async (replacement) => {
    try {
        const oldCode = replacement.oldCode || replacement.old_code
        const newCode = replacement.newCode || replacement.new_code

        if (currentCode.value.includes(oldCode)) {
            currentCode.value = currentCode.value.replace(oldCode, newCode)
            hasChanges.value = true
            updatePreview()
            toast.success('Code-Änderung angewendet')
        } else {
            toast.error('Code-Fragment nicht gefunden')
        }
    } catch (error) {
        console.error('Error applying replacement:', error)
        toast.error('Fehler beim Anwenden der Änderung')
    }
}

const scrollChatToBottom = async () => {
    await nextTick()
    if (chatMessages.value) {
        chatMessages.value.scrollTop = chatMessages.value.scrollHeight
    }
}

watch([htmlCode, cssCode, jsCode], () => {
    if (updatePreviewTimeout) clearTimeout(updatePreviewTimeout)
    updatePreviewTimeout = setTimeout(() => {
        updatePreview()
    }, 500)
})

onMounted(async () => {
    await loadPageData()
})
</script>

<style scoped>
.wb-page-view {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #1e1e1e;
    color: #d4d4d4;
}

.wb-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: #252526;
    border-bottom: 1px solid #3c3c3c;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.back-btn {
    background: transparent;
    border: none;
    color: #cccccc;
    font-size: 20px;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
}

.back-btn:hover {
    color: #ffffff;
}

.page-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: #cccccc;
}

.page-slug {
    font-size: 13px;
    color: #858585;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-toggle-btn {
    background: transparent;
    border: 1px solid #3c3c3c;
    color: #cccccc;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
}

.view-toggle-btn:hover {
    background: #2a2d2e;
    border-color: #007acc;
}

.view-toggle-btn.active {
    background: #007acc;
    border-color: #007acc;
    color: white;
}

.save-btn,
.publish-btn {
    background: #007acc;
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 500;
}

.save-btn:hover,
.publish-btn:hover {
    background: #005a9e;
}

.save-btn:disabled {
    background: #3c3c3c;
    cursor: not-allowed;
    opacity: 0.6;
}

.publish-btn {
    background: #28a745;
}

.publish-btn:hover {
    background: #218838;
}

.wb-content {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.wb-content.view-split .editor-panel {
    width: 50%;
    border-right: 1px solid #3c3c3c;
}

.wb-content.view-split .preview-panel {
    width: 50%;
}

.wb-content.view-code .editor-panel {
    width: 100%;
}

.wb-content.view-code .preview-panel {
    display: none;
}

.wb-content.view-preview .editor-panel {
    display: none;
}

.wb-content.view-preview .preview-panel {
    width: 100%;
}

.editor-panel {
    display: flex;
    flex-direction: column;
    background: #1e1e1e;
}

.editor-tabs {
    display: flex;
    background: #252526;
    border-bottom: 1px solid #3c3c3c;
}

.editor-tab {
    background: transparent;
    border: none;
    color: #858585;
    padding: 10px 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.editor-tab:hover {
    color: #cccccc;
    background: #2a2d2e;
}

.editor-tab.active {
    color: #ffffff;
    border-bottom-color: #007acc;
}

.editor-container {
    flex: 1;
    overflow: hidden;
}

.preview-panel {
    display: flex;
    flex-direction: column;
    background: #2d2d30;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #252526;
    border-bottom: 1px solid #3c3c3c;
}

.preview-device-selector {
    display: flex;
    gap: 4px;
}

.device-btn {
    background: transparent;
    border: 1px solid #3c3c3c;
    color: #cccccc;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    font-size: 18px;
}

.device-btn:hover {
    background: #2a2d2e;
    border-color: #007acc;
}

.device-btn.active {
    background: #007acc;
    border-color: #007acc;
    color: white;
}

.refresh-preview-btn {
    background: transparent;
    border: 1px solid #3c3c3c;
    color: #cccccc;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    font-size: 18px;
}

.refresh-preview-btn:hover {
    background: #2a2d2e;
    border-color: #007acc;
}

.preview-viewport {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 20px;
    overflow: auto;
}

.preview-viewport.device-desktop .preview-iframe {
    width: 100%;
    height: 100%;
}

.preview-viewport.device-tablet .preview-iframe {
    width: 768px;
    height: 1024px;
    border: 1px solid #3c3c3c;
    border-radius: 8px;
}

.preview-viewport.device-mobile .preview-iframe {
    width: 375px;
    height: 667px;
    border: 1px solid #3c3c3c;
    border-radius: 12px;
}

.preview-iframe {
    background: white;
    border: none;
}

.ai-assistant-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
}

.ai-assistant-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
}

.ai-assistant-button.active {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.ai-icon {
    font-size: 24px;
    font-weight: bold;
    color: white;
    font-style: normal;
}

.ai-chat-modal {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 450px;
    height: 600px;
    background: #252526;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.chat-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.chat-controls {
    display: flex;
    gap: 8px;
}

.mode-toggle,
.close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}

.mode-toggle:hover,
.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.mode-toggle.active {
    background: rgba(255, 255, 255, 0.4);
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #1e1e1e;
}

.message {
    margin-bottom: 16px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.user .message-content {
    background: #007acc;
    color: white;
    padding: 12px 16px;
    border-radius: 12px 12px 4px 12px;
    margin-left: auto;
    max-width: 80%;
}

.message.ai .message-content {
    background: #2d2d30;
    color: #d4d4d4;
    padding: 12px 16px;
    border-radius: 12px 12px 12px 4px;
    max-width: 80%;
}

.message-time {
    font-size: 11px;
    color: #858585;
    margin-top: 4px;
    text-align: right;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #007acc;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {

    0%,
    60%,
    100% {
        transform: translateY(0);
        opacity: 0.7;
    }

    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.code-replacements {
    margin-top: 12px;
}

.replacement-item {
    background: #2d2d30;
    border: 1px solid #3c3c3c;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
}

.apply-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin-bottom: 8px;
}

.apply-btn:hover {
    background: #218838;
}

.replacement-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
}

.old-code,
.new-code {
    background: #1e1e1e;
    padding: 8px;
    border-radius: 4px;
    font-size: 12px;
}

.old-code pre,
.new-code pre {
    margin: 4px 0 0 0;
    white-space: pre-wrap;
    word-break: break-all;
    font-family: 'Monaco', 'Menlo', monospace;
}

.chat-input {
    display: flex;
    gap: 8px;
    padding: 16px;
    background: #252526;
    border-top: 1px solid #3c3c3c;
}

.chat-input textarea {
    flex: 1;
    background: #1e1e1e;
    border: 1px solid #3c3c3c;
    color: #d4d4d4;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: none;
    min-height: 44px;
    max-height: 120px;
}

.chat-input textarea:focus {
    outline: none;
    border-color: #007acc;
}

.send-btn {
    background: #007acc;
    color: white;
    border: none;
    padding: 0 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
    transition: all 0.2s;
}

.send-btn:hover:not(:disabled) {
    background: #005a9e;
}

.send-btn:disabled {
    background: #3c3c3c;
    cursor: not-allowed;
    opacity: 0.6;
}
</style>