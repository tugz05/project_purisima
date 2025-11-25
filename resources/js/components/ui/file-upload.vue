<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Upload } from 'lucide-vue-next';

interface Props {
    accept?: string;
    multiple?: boolean;
    maxSize?: number; // in MB
}

const props = withDefaults(defineProps<Props>(), {
    accept: '*',
    multiple: false,
    maxSize: 10,
});

const emit = defineEmits<{
    upload: [files: File[]];
}>();

const fileInput = ref<HTMLInputElement>();
const isDragging = ref(false);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        emit('upload', files);
    }
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    if (event.dataTransfer?.files) {
        const files = Array.from(event.dataTransfer.files);
        emit('upload', files);
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const openFileDialog = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div>
        <input
            ref="fileInput"
            type="file"
            :accept="accept"
            :multiple="multiple"
            @change="handleFileSelect"
            class="hidden"
        />

        <div
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @click="openFileDialog"
            :class="[
                'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
                isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
            ]"
        >
            <Upload class="h-8 w-8 mx-auto mb-2 text-gray-400" />
            <p class="text-sm text-gray-600">
                Click to upload or drag and drop
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ accept === '*' ? 'Any file type' : accept }} (max {{ maxSize }}MB)
            </p>
        </div>
    </div>
</template>

