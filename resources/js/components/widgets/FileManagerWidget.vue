<template>
  <div class="file-manager-widget h-100">
    <div class="file-header d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">{{ config.title || 'File Manager' }}</h6>
      <div class="file-controls" v-if="!isPreview">
        <button class="btn btn-success btn-sm" @click="uploadFiles">
          <i class="fas fa-upload"></i> Upload
        </button>
        <button class="btn btn-primary btn-sm" @click="createFolder">
          <i class="fas fa-folder-plus"></i> New Folder
        </button>
      </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb-nav mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="#" @click="navigateToFolder(null)">
            <i class="fas fa-home"></i> Home
          </a>
        </li>
        <li
          v-for="(folder, index) in breadcrumbs"
          :key="index"
          class="breadcrumb-item"
          :class="{ active: index === breadcrumbs.length - 1 }"
        >
          <a v-if="index < breadcrumbs.length - 1" href="#" @click="navigateToFolder(folder.id)">
            {{ folder.name }}
          </a>
          <span v-else>{{ folder.name }}</span>
        </li>
      </ol>
    </nav>

    <!-- File List -->
    <div class="file-list-container">
      <div class="file-list">
        <!-- Folders -->
        <div
          v-for="folder in currentFolders"
          :key="'folder-' + folder.id"
          class="file-item folder-item"
          @click="navigateToFolder(folder.id)"
          @contextmenu="showContextMenu($event, folder, 'folder')"
        >
          <div class="file-icon">
            <i class="fas fa-folder text-warning"></i>
          </div>
          <div class="file-info">
            <div class="file-name">{{ folder.name }}</div>
            <div class="file-meta">{{ folder.items }} items</div>
          </div>
        </div>

        <!-- Files -->
        <div
          v-for="file in currentFiles"
          :key="'file-' + file.id"
          class="file-item"
          @click="selectFile(file)"
          @dblclick="openFile(file)"
          @contextmenu="showContextMenu($event, file, 'file')"
          :class="{ selected: selectedFile?.id === file.id }"
        >
          <div class="file-icon">
            <i :class="getFileIcon(file)"></i>
          </div>
          <div class="file-info">
            <div class="file-name">{{ file.name }}</div>
            <div class="file-meta">
              {{ formatFileSize(file.size) }} • {{ formatDate(file.modified) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="currentFolders.length === 0 && currentFiles.length === 0" class="empty-state">
        <div class="text-center text-muted">
          <i class="fas fa-folder-open fa-3x mb-3"></i>
          <h6>No files or folders</h6>
          <p class="small">Upload files or create folders to get started</p>
        </div>
      </div>
    </div>

    <!-- File Upload Input -->
    <input
      ref="fileInput"
      type="file"
      multiple
      style="display: none"
      @change="handleFileUpload"
    />

    <!-- Context Menu -->
    <div
      v-if="contextMenu.show"
      class="context-menu"
      :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
      @blur="hideContextMenu"
      tabindex="-1"
    >
      <div class="list-group">
        <a class="list-group-item list-group-item-action" @click="downloadItem">
          <i class="fas fa-download me-2"></i> Download
        </a>
        <a class="list-group-item list-group-item-action" @click="renameItem">
          <i class="fas fa-edit me-2"></i> Rename
        </a>
        <a class="list-group-item list-group-item-action" @click="shareItem">
          <i class="fas fa-share me-2"></i> Share
        </a>
        <div class="dropdown-divider"></div>
        <a class="list-group-item list-group-item-action text-danger" @click="deleteItem">
          <i class="fas fa-trash me-2"></i> Delete
        </a>
      </div>
    </div>

    <!-- File Preview Modal -->
    <div v-if="previewFile" class="file-preview-modal" @click="closePreview">
      <div class="preview-content" @click.stop>
        <div class="preview-header">
          <h6>{{ previewFile.name }}</h6>
          <button class="btn btn-outline-light btn-sm" @click="closePreview">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="preview-body">
          <img v-if="isImage(previewFile)" :src="previewFile.url" alt="Preview" class="preview-image">
          <div v-else-if="isText(previewFile)" class="preview-text">
            <pre>{{ previewFile.content }}</pre>
          </div>
          <div v-else class="preview-placeholder">
            <i :class="getFileIcon(previewFile)" class="fa-4x mb-3"></i>
            <p>{{ previewFile.name }}</p>
            <button class="btn btn-primary" @click="downloadFile(previewFile)">
              <i class="fas fa-download"></i> Download
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  data: Object,
  config: Object,
  isPreview: Boolean,
  instanceId: String
})

// State
const currentFolderId = ref(null)
const selectedFile = ref(null)
const previewFile = ref(null)
const fileInput = ref(null)
const contextMenu = ref({ show: false, x: 0, y: 0, item: null, type: null })

// Sample file system data
const fileSystem = ref({
  folders: [
    { id: 1, name: 'Documents', parent: null, items: 5 },
    { id: 2, name: 'Images', parent: null, items: 12 },
    { id: 3, name: 'Academic Records', parent: 1, items: 8 },
    { id: 4, name: 'Certificates', parent: 1, items: 3 }
  ],
  files: [
    {
      id: 1,
      name: 'Student_List_2024.xlsx',
      parent: null,
      size: 2048576,
      type: 'spreadsheet',
      modified: '2024-01-15T10:30:00Z',
      url: '#'
    },
    {
      id: 2,
      name: 'Campus_Photo.jpg',
      parent: 2,
      size: 1536000,
      type: 'image',
      modified: '2024-01-14T15:45:00Z',
      url: 'https://via.placeholder.com/800x600/0066cc/ffffff?text=Campus+Photo'
    },
    {
      id: 3,
      name: 'Syllabus_2024.pdf',
      parent: 3,
      size: 512000,
      type: 'pdf',
      modified: '2024-01-10T09:15:00Z',
      url: '#'
    }
  ]
})

// Computed
const currentFolders = computed(() =>
  fileSystem.value.folders.filter(folder => folder.parent === currentFolderId.value)
)

const currentFiles = computed(() =>
  fileSystem.value.files.filter(file => file.parent === currentFolderId.value)
)

const breadcrumbs = computed(() => {
  const path = []
  let folderId = currentFolderId.value
  
  while (folderId) {
    const folder = fileSystem.value.folders.find(f => f.id === folderId)
    if (folder) {
      path.unshift(folder)
      folderId = folder.parent
    } else {
      break
    }
  }
  
  return path
})

// Methods
const navigateToFolder = (folderId) => {
  currentFolderId.value = folderId
  selectedFile.value = null
}

const selectFile = (file) => {
  selectedFile.value = file
}

const openFile = (file) => {
  if (isImage(file) || isText(file)) {
    previewFile.value = file
  } else {
    downloadFile(file)
  }
}

const uploadFiles = () => {
  fileInput.value?.click()
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  
  files.forEach(file => {
    const newFile = {
      id: Date.now() + Math.random(),
      name: file.name,
      parent: currentFolderId.value,
      size: file.size,
      type: getFileTypeFromName(file.name),
      modified: new Date().toISOString(),
      url: URL.createObjectURL(file)
    }
    
    fileSystem.value.files.push(newFile)
  })
  
  // Clear input
  event.target.value = ''
}

const createFolder = () => {
  const name = prompt('Enter folder name:')
  if (name) {
    const newFolder = {
      id: Date.now(),
      name: name,
      parent: currentFolderId.value,
      items: 0
    }
    
    fileSystem.value.folders.push(newFolder)
  }
}

const showContextMenu = (event, item, type) => {
  event.preventDefault()
  contextMenu.value = {
    show: true,
    x: event.clientX,
    y: event.clientY,
    item: item,
    type: type
  }
  
  // Focus for blur event
  setTimeout(() => {
    document.querySelector('.context-menu')?.focus()
  }, 10)
}

const hideContextMenu = () => {
  contextMenu.value.show = false
}

const downloadItem = () => {
  if (contextMenu.value.type === 'file') {
    downloadFile(contextMenu.value.item)
  }
  hideContextMenu()
}

const downloadFile = (file) => {
 // Simulate download
 const a = document.createElement('a')
 a.href = file.url || '#'
 a.download = file.name
 document.body.appendChild(a)
 a.click()
 document.body.removeChild(a)
}

const renameItem = () => {
 const item = contextMenu.value.item
 const newName = prompt(`Rename ${contextMenu.value.type}:`, item.name)
 
 if (newName && newName !== item.name) {
   item.name = newName
 }
 hideContextMenu()
}

const shareItem = () => {
 const item = contextMenu.value.item
 // Simulate sharing
 const shareUrl = `${window.location.origin}/shared/${item.id}`
 
 if (navigator.share) {
   navigator.share({
     title: item.name,
     url: shareUrl
   })
 } else {
   // Fallback: copy to clipboard
   navigator.clipboard.writeText(shareUrl).then(() => {
     alert('Share link copied to clipboard!')
   })
 }
 hideContextMenu()
}

const deleteItem = () => {
 const item = contextMenu.value.item
 const type = contextMenu.value.type
 
 if (confirm(`Are you sure you want to delete "${item.name}"?`)) {
   if (type === 'folder') {
     const index = fileSystem.value.folders.findIndex(f => f.id === item.id)
     if (index > -1) {
       fileSystem.value.folders.splice(index, 1)
     }
   } else {
     const index = fileSystem.value.files.findIndex(f => f.id === item.id)
     if (index > -1) {
       fileSystem.value.files.splice(index, 1)
     }
   }
 }
 hideContextMenu()
}

const closePreview = () => {
 previewFile.value = null
}

const getFileIcon = (file) => {
 const iconMap = {
   pdf: 'fas fa-file-pdf text-danger',
   doc: 'fas fa-file-word text-primary',
   docx: 'fas fa-file-word text-primary',
   xls: 'fas fa-file-excel text-success',
   xlsx: 'fas fa-file-excel text-success',
   ppt: 'fas fa-file-powerpoint text-warning',
   pptx: 'fas fa-file-powerpoint text-warning',
   jpg: 'fas fa-file-image text-info',
   jpeg: 'fas fa-file-image text-info',
   png: 'fas fa-file-image text-info',
   gif: 'fas fa-file-image text-info',
   txt: 'fas fa-file-alt text-secondary',
   zip: 'fas fa-file-archive text-dark',
   rar: 'fas fa-file-archive text-dark',
   mp4: 'fas fa-file-video text-purple',
   mp3: 'fas fa-file-audio text-success',
   default: 'fas fa-file text-muted'
 }
 
 const extension = file.name.split('.').pop()?.toLowerCase()
 return iconMap[extension] || iconMap.default
}

const getFileTypeFromName = (filename) => {
 const extension = filename.split('.').pop()?.toLowerCase()
 const typeMap = {
   jpg: 'image', jpeg: 'image', png: 'image', gif: 'image',
   pdf: 'pdf',
   doc: 'document', docx: 'document',
   xls: 'spreadsheet', xlsx: 'spreadsheet',
   txt: 'text',
   zip: 'archive', rar: 'archive',
   mp4: 'video',
   mp3: 'audio'
 }
 return typeMap[extension] || 'file'
}

const isImage = (file) => {
 return ['jpg', 'jpeg', 'png', 'gif'].includes(
   file.name.split('.').pop()?.toLowerCase()
 )
}

const isText = (file) => {
 return ['txt', 'md', 'json'].includes(
   file.name.split('.').pop()?.toLowerCase()
 )
}

const formatFileSize = (bytes) => {
 const sizes = ['Bytes', 'KB', 'MB', 'GB']
 if (bytes === 0) return '0 Bytes'
 const i = Math.floor(Math.log(bytes) / Math.log(1024))
 return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (dateString) => {
 return new Date(dateString).toLocaleDateString('en-US', {
   year: 'numeric',
   month: 'short',
   day: 'numeric',
   hour: '2-digit',
   minute: '2-digit'
 })
}

// Click outside to hide context menu
onMounted(() => {
 document.addEventListener('click', (e) => {
   if (!e.target.closest('.context-menu')) {
     hideContextMenu()
   }
 })
})
</script>

<style scoped>
.file-manager-widget {
 font-size: 13px;
}

.breadcrumb {
 background: none;
 padding: 0;
 font-size: 12px;
}

.breadcrumb-item a {
 color: #6c757d;
 text-decoration: none;
}

.breadcrumb-item a:hover {
 color: #495057;
}

.file-list-container {
 flex: 1;
 overflow-y: auto;
 max-height: calc(100% - 120px);
}

.file-list {
 display: grid;
 grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
 gap: 8px;
}

.file-item {
 display: flex;
 align-items: center;
 padding: 8px;
 border: 1px solid #dee2e6;
 border-radius: 6px;
 cursor: pointer;
 transition: all 0.2s ease;
 background: white;
}

.file-item:hover {
 border-color: #0d6efd;
 background: #f8f9ff;
 transform: translateY(-1px);
 box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.file-item.selected {
 border-color: #0d6efd;
 background: #e7f1ff;
}

.folder-item {
 border-color: #ffc107;
}

.folder-item:hover {
 border-color: #ffca2c;
 background: #fff8e1;
}

.file-icon {
 margin-right: 8px;
 font-size: 24px;
 width: 32px;
 text-align: center;
}

.file-info {
 flex: 1;
 min-width: 0;
}

.file-name {
 font-weight: 500;
 white-space: nowrap;
 overflow: hidden;
 text-overflow: ellipsis;
 margin-bottom: 2px;
}

.file-meta {
 font-size: 11px;
 color: #6c757d;
}

.empty-state {
 grid-column: 1 / -1;
 padding: 40px 20px;
}

.context-menu {
 position: fixed;
 z-index: 1050;
 background: white;
 border: 1px solid #dee2e6;
 border-radius: 6px;
 box-shadow: 0 4px 12px rgba(0,0,0,0.15);
 min-width: 150px;
}

.context-menu .list-group-item {
 border: none;
 padding: 8px 12px;
 font-size: 13px;
 cursor: pointer;
}

.context-menu .list-group-item:hover {
 background: #f8f9fa;
}

.file-preview-modal {
 position: fixed;
 top: 0;
 left: 0;
 right: 0;
 bottom: 0;
 background: rgba(0, 0, 0, 0.8);
 display: flex;
 align-items: center;
 justify-content: center;
 z-index: 1060;
}

.preview-content {
 background: white;
 border-radius: 8px;
 max-width: 90vw;
 max-height: 90vh;
 overflow: hidden;
 display: flex;
 flex-direction: column;
}

.preview-header {
 padding: 12px 16px;
 border-bottom: 1px solid #dee2e6;
 display: flex;
 justify-content: between;
 align-items: center;
 background: #f8f9fa;
}

.preview-header h6 {
 margin: 0;
 flex: 1;
}

.preview-body {
 padding: 16px;
 flex: 1;
 overflow: auto;
 text-align: center;
}

.preview-image {
 max-width: 100%;
 max-height: 70vh;
 border-radius: 4px;
}

.preview-text {
 text-align: left;
 background: #f8f9fa;
 padding: 16px;
 border-radius: 4px;
 max-height: 60vh;
 overflow: auto;
}

.preview-text pre {
 margin: 0;
 font-size: 12px;
 white-space: pre-wrap;
}

.preview-placeholder {
 padding: 40px;
 color: #6c757d;
}

.file-controls button {
 font-size: 12px;
 padding: 4px 8px;
}
</style>