<template>
  <div class="vue-quill-editor">
    <QuillEditor
      v-model:content="content"
      content-type="html"
      :options="editorOptions"
      @update:content="handleContentUpdate"
      class="min-h-[200px]"
    />

    <!-- Custom Attachment Button -->
    <div class="attachment-button-container">
      <button
        type="button"
        @click="handleAttachmentUpload"
        class="attachment-button"
        title="Insert Attachment"
      >
        📎 Insert Attachment
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

// Props
const props = defineProps<{
  modelValue: string;
}>();

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

// Reactive content
const content = ref(props.modelValue || '');

// Editor options
const editorOptions = {
  theme: 'snow',
  placeholder: 'Start writing your announcement...',
  modules: {
    toolbar: [
      [{ 'header': [1, 2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ 'color': [] }, { 'background': [] }],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      [{ 'align': [] }],
      ['link', 'blockquote', 'code-block'],
      ['image'],
      ['clean']
    ]
  }
};

// Handle content updates
const handleContentUpdate = (value: string) => {
  emit('update:modelValue', value);
};

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (content.value !== newValue) {
    content.value = newValue || '';
  }
});

// Custom handlers setup for VueQuill
const setupCustomHandlers = () => {
  const toolbar = document.querySelector('.ql-toolbar');
  if (toolbar) {
    // Override the default image handler
    const imageButton = toolbar.querySelector('.ql-image');
    if (imageButton) {
      imageButton.addEventListener('click', (e) => {
        e.preventDefault();
        handleImageUpload();
      });
    }
  }
};

// Enhanced image upload handler
const handleImageUpload = () => {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.setAttribute('multiple', 'true');
  input.click();

  input.onchange = () => {
    const files = input.files;
    if (files && files.length > 0) {
      Array.from(files).forEach((file) => {
        if (file.type.startsWith('image/')) {
          uploadImageToServer(file);
        }
      });
    }
  };
};

// Upload image to server
const uploadImageToServer = async (file: File) => {
  try {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch('/api/upload-image', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    if (response.ok) {
      const data = await response.json();
      insertImageToEditor(data.image_url, file.name);
    } else {
      throw new Error('Upload failed');
    }
  } catch (error) {
    console.error('Image upload error:', error);
    // Fallback to base64 if server upload fails
    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      if (result) {
        insertImageToEditor(result, file.name);
      }
    };
    reader.readAsDataURL(file);
  }
};

// Insert image into VueQuill editor
const insertImageToEditor = (imageSrc: string, fileName: string) => {
  const editor = document.querySelector('.ql-editor') as HTMLElement;
  if (editor) {
    // Create image element with enhanced styling
    const img = document.createElement('img');
    img.src = imageSrc;
    img.alt = fileName;
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.borderRadius = '12px';
    img.style.margin = '16px 0';
    img.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
    img.style.transition = 'transform 0.2s ease';
    img.style.cursor = 'pointer';

    // Add hover effect
    img.addEventListener('mouseenter', () => {
      img.style.transform = 'scale(1.02)';
    });
    img.addEventListener('mouseleave', () => {
      img.style.transform = 'scale(1)';
    });

    // Insert at cursor position
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      range.insertNode(img);

      // Add a line break after image
      const br = document.createElement('br');
      range.insertNode(br);

      // Move cursor after the image
      range.setStartAfter(br);
      range.setEndAfter(br);
      selection.removeAllRanges();
      selection.addRange(range);
    }

    // Trigger content update
    handleContentUpdate(editor.innerHTML);
  }
};

// Attachment upload handler
const handleAttachmentUpload = () => {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('multiple', 'true');
  input.click();

  input.onchange = () => {
    const files = input.files;
    if (files && files.length > 0) {
      Array.from(files).forEach((file) => {
        insertAttachmentToEditor(file);
      });
    }
  };
};

// Insert attachment into VueQuill editor
const insertAttachmentToEditor = (file: File) => {
  const editor = document.querySelector('.ql-editor') as HTMLElement;
  if (editor) {
    // Create attachment element
    const attachmentDiv = document.createElement('div');
    attachmentDiv.className = 'attachment-item';
    attachmentDiv.style.cssText = `
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      margin: 8px 0;
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border: 2px solid #cbd5e1;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      max-width: 100%;
    `;

    // Add hover effect
    attachmentDiv.addEventListener('mouseenter', () => {
      attachmentDiv.style.borderColor = '#3b82f6';
      attachmentDiv.style.background = 'linear-gradient(135deg, #eff6ff, #dbeafe)';
      attachmentDiv.style.transform = 'translateY(-1px)';
      attachmentDiv.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
    });
    attachmentDiv.addEventListener('mouseleave', () => {
      attachmentDiv.style.borderColor = '#cbd5e1';
      attachmentDiv.style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
      attachmentDiv.style.transform = 'translateY(0)';
      attachmentDiv.style.boxShadow = 'none';
    });

    // File icon based on type
    const getFileIcon = (fileName: string) => {
      const extension = fileName.split('.').pop()?.toLowerCase();
      switch (extension) {
        case 'pdf': return '📄';
        case 'doc':
        case 'docx': return '📝';
        case 'xls':
        case 'xlsx': return '📊';
        case 'ppt':
        case 'pptx': return '📋';
        case 'zip':
        case 'rar': return '📦';
        case 'txt': return '📃';
        default: return '📎';
      }
    };

    // Create attachment content
    attachmentDiv.innerHTML = `
      <span style="font-size: 20px;">${getFileIcon(file.name)}</span>
      <div style="flex: 1; min-width: 0;">
        <div style="font-weight: 600; color: #1e293b; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
          ${file.name}
        </div>
        <div style="font-size: 12px; color: #64748b;">
          ${(file.size / 1024 / 1024).toFixed(2)} MB
        </div>
      </div>
      <span style="color: #3b82f6; font-size: 12px; font-weight: 500;">Download</span>
    `;

    // Add click handler for download
    attachmentDiv.addEventListener('click', () => {
      const url = URL.createObjectURL(file);
      const a = document.createElement('a');
      a.href = url;
      a.download = file.name;
      a.click();
      URL.revokeObjectURL(url);
    });

    // Insert at cursor position
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      range.insertNode(attachmentDiv);

      // Add a line break after attachment
      const br = document.createElement('br');
      range.insertNode(br);

      // Move cursor after the attachment
      range.setStartAfter(br);
      range.setEndAfter(br);
      selection.removeAllRanges();
      selection.addRange(range);
    }

    // Trigger content update
    handleContentUpdate(editor.innerHTML);
  }
};

// Setup handlers when component mounts
import { onMounted } from 'vue';
onMounted(() => {
  // Wait for VueQuill to initialize
  setTimeout(() => {
    setupCustomHandlers();
  }, 100);
});
</script>

<style scoped>
.vue-quill-editor {
  border: 2px solid hsl(210 40% 90%);
  border-radius: 0.75rem;
  overflow: hidden;
  transition: border-color 0.2s ease;
}

.vue-quill-editor:focus-within {
  border-color: hsl(262 83% 58%);
  box-shadow: 0 0 0 4px hsl(262 83% 58% / 0.1);
}

/* Custom Quill styles */
:deep(.ql-toolbar) {
  border-bottom: 1px solid hsl(210 40% 90%);
  background-color: hsl(210 40% 98%);
  padding: 1rem;
}

:deep(.ql-container) {
  border: none;
  font-size: 1rem;
  line-height: 1.6;
}

:deep(.ql-editor) {
  padding: 1.5rem;
  min-height: 400px;
  font-size: 1.1rem;
  line-height: 1.7;
}

:deep(.ql-editor.ql-blank::before) {
  color: hsl(215 16% 45%);
  font-style: normal;
}

/* Custom button styles */
:deep(.ql-toolbar .ql-formats button) {
  border-radius: 0.375rem;
  margin: 0.125rem;
  transition: background-color 0.2s ease;
}

:deep(.ql-toolbar .ql-formats button:hover) {
  background-color: hsl(210 40% 90%);
}

:deep(.ql-toolbar .ql-formats button.ql-active) {
  background-color: hsl(262 83% 58%);
  color: white;
}

/* Link styles */
:deep(.ql-editor a) {
  color: hsl(221 83% 53%);
  text-decoration: underline;
}

:deep(.ql-editor a:hover) {
  color: hsl(221 83% 43%);
}

/* Blockquote styles */
:deep(.ql-editor blockquote) {
  border-left: 4px solid hsl(262 83% 58%);
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: hsl(215 16% 45%);
}

/* Code block styles */
:deep(.ql-editor pre.ql-syntax) {
  background-color: hsl(210 40% 95%);
  border-radius: 0.5rem;
  padding: 1rem;
  margin: 1rem 0;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  overflow-x: auto;
}

/* Image styles to match Welcome.vue */
:deep(.ql-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.75rem;
  margin: 1rem 0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease;
}

:deep(.ql-editor img:hover) {
  transform: scale(1.02);
}

/* Enhanced toolbar styling */
:deep(.ql-toolbar) {
  border-bottom: 2px solid hsl(210 40% 90%);
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 12px 12px 0 0;
}

:deep(.ql-toolbar .ql-formats) {
  margin-right: 15px;
}

:deep(.ql-toolbar button) {
  border-radius: 6px;
  transition: all 0.2s ease;
}

:deep(.ql-toolbar button:hover) {
  background-color: hsl(210 40% 95%);
  transform: translateY(-1px);
}

:deep(.ql-toolbar button.ql-active) {
  background-color: hsl(210 40% 90%);
  color: hsl(210 40% 20%);
}

/* Custom attachment button */
.attachment-button-container {
  padding: 0.75rem;
  border-top: 1px solid hsl(210 40% 90%);
  background-color: hsl(210 40% 98%);
  border-radius: 0 0 12px 12px;
}

.attachment-button {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.attachment-button:hover {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.attachment-button:active {
  transform: translateY(0);
}
</style>
