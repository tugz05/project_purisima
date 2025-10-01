<template>
  <div class="rich-text-editor">
    <!-- Toolbar -->
    <div class="toolbar border-b p-2 flex flex-wrap gap-1" style="border-color: hsl(210 40% 90%); background-color: hsl(210 40% 96%);">
      <!-- Text Formatting -->
      <div class="flex items-center gap-1 border-r pr-2 mr-2" style="border-color: hsl(210 40% 85%);">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleBold().run()"
          :class="{ 'bg-gray-200': editor?.isActive('bold') }"
          class="h-8 w-8 p-0"
        >
          <BoldIcon class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleItalic().run()"
          :class="{ 'bg-gray-200': editor?.isActive('italic') }"
          class="h-8 w-8 p-0"
        >
          <ItalicIcon class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleUnderline().run()"
          :class="{ 'bg-gray-200': editor?.isActive('underline') }"
          class="h-8 w-8 p-0"
        >
          <Underline class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleStrike().run()"
          :class="{ 'bg-gray-200': editor?.isActive('strike') }"
          class="h-8 w-8 p-0"
        >
          <Strikethrough class="w-4 h-4" />
        </Button>
      </div>

      <!-- Headings -->
      <div class="flex items-center gap-1 border-r pr-2 mr-2" style="border-color: hsl(210 40% 85%);">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ 'bg-gray-200': editor?.isActive('heading', { level: 1 }) }"
          class="h-8 px-2 text-sm font-bold"
        >
          H1
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ 'bg-gray-200': editor?.isActive('heading', { level: 2 }) }"
          class="h-8 px-2 text-sm font-bold"
        >
          H2
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="{ 'bg-gray-200': editor?.isActive('heading', { level: 3 }) }"
          class="h-8 px-2 text-sm font-bold"
        >
          H3
        </Button>
      </div>

      <!-- Lists -->
      <div class="flex items-center gap-1 border-r pr-2 mr-2" style="border-color: hsl(210 40% 85%);">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleBulletList().run()"
          :class="{ 'bg-gray-200': editor?.isActive('bulletList') }"
          class="h-8 w-8 p-0"
        >
          <List class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().toggleOrderedList().run()"
          :class="{ 'bg-gray-200': editor?.isActive('orderedList') }"
          class="h-8 w-8 p-0"
        >
          <ListOrdered class="w-4 h-4" />
        </Button>
      </div>

      <!-- Alignment -->
      <div class="flex items-center gap-1 border-r pr-2 mr-2" style="border-color: hsl(210 40% 85%);">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().setTextAlign('left').run()"
          :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'left' }) }"
          class="h-8 w-8 p-0"
        >
          <AlignLeft class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().setTextAlign('center').run()"
          :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'center' }) }"
          class="h-8 w-8 p-0"
        >
          <AlignCenter class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().setTextAlign('right').run()"
          :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'right' }) }"
          class="h-8 w-8 p-0"
        >
          <AlignRight class="w-4 h-4" />
        </Button>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-1 border-r pr-2 mr-2" style="border-color: hsl(210 40% 85%);">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="setLink"
          :class="{ 'bg-gray-200': editor?.isActive('link') }"
          class="h-8 w-8 p-0"
        >
          <LinkIcon class="w-4 h-4" />
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          @click="editor?.chain().focus().unsetLink().run()"
          :disabled="!editor?.isActive('link')"
          class="h-8 w-8 p-0"
        >
          <Unlink class="w-4 h-4" />
        </Button>
      </div>

    </div>

    <!-- Editor Content -->
    <div class="editor-content" @click="focusEditor">
      <editor-content :editor="editor" class="prose prose-sm max-w-none p-4 min-h-[200px] focus:outline-none" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import LinkExtension from '@tiptap/extension-link';
import { Button } from '@/components/ui/button';
import {
  Bold as BoldIcon,
  Italic as ItalicIcon,
  Underline as UnderlineIcon,
  Strikethrough,
  List,
  ListOrdered,
  AlignLeft,
  AlignCenter,
  AlignRight,
  Link as LinkIcon,
  Unlink,
} from 'lucide-vue-next';

// Props
const props = defineProps<{
  modelValue: string;
}>();

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

// Editor
const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    // Use StarterKit with specific extensions disabled to avoid conflicts
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3],
      },
      link: false, // Disable to use our custom LinkExtension
      underline: false, // Disable to use our custom Underline
    }),

    // Text alignment
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),

    // Custom extensions
    Underline,
    LinkExtension.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-blue-600 underline',
      },
    }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML());
  },
  onCreate: ({ editor }) => {
    // Ensure editor is focused and ready
    editor.commands.focus();
  },
});

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue);
  }
});

// Methods
const focusEditor = () => {
  if (editor.value) {
    editor.value.commands.focus();
  }
};

const setLink = () => {
  if (!editor.value) return;

  const url = window.prompt('Enter URL:');
  if (url) {
    editor.value.chain().focus().setLink({ href: url }).run();
  }
};

// Debug editor initialization
onMounted(() => {
  console.log('RichTextEditor mounted, editor:', editor.value);
  if (editor.value) {
    console.log('Editor is ready, content:', editor.value.getHTML());
  }
});

// Cleanup
onBeforeUnmount(() => {
  editor.value?.destroy();
});
</script>

<style scoped>
.rich-text-editor {
  border: 1px solid hsl(210 40% 90%);
  border-radius: 0.5rem;
  overflow: hidden;
}

.toolbar {
  flex-wrap: wrap;
}

.editor-content :deep(.ProseMirror) {
  outline: none;
  min-height: 200px;
  padding: 1rem;
}

.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: hsl(215 16% 45%);
  float: left;
  height: 0;
  pointer-events: none;
  content: attr(data-placeholder);
}

.editor-content :deep(.ProseMirror h1) {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  margin-top: 1.5rem;
}

.editor-content :deep(.ProseMirror h2) {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  margin-top: 1.25rem;
}

.editor-content :deep(.ProseMirror h3) {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  margin-top: 1rem;
}

.editor-content :deep(.ProseMirror ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 1rem;
}

.editor-content :deep(.ProseMirror ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 1rem;
}

.editor-content :deep(.ProseMirror li) {
  margin-bottom: 0.25rem;
}

.editor-content :deep(.ProseMirror a) {
  color: hsl(221 83% 53%);
  text-decoration: underline;
}

.editor-content :deep(.ProseMirror a:hover) {
  color: hsl(221 83% 40%);
}

.editor-content :deep(.ProseMirror strong) {
  font-weight: 700;
}

.editor-content :deep(.ProseMirror em) {
  font-style: italic;
}

.editor-content :deep(.ProseMirror u) {
  text-decoration: underline;
}

.editor-content :deep(.ProseMirror s) {
  text-decoration: line-through;
}
</style>
