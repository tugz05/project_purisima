import { ref, type Ref } from 'vue';
import {
    mergeMessagingPendingAttachments,
    normalizeMessagingClipboardFiles,
    normalizeMessagingDroppedFiles,
} from '@/utils/messagingComposerUpload';

export function useMessagingInPageUpload(
    pendingFiles: Ref<File[]>,
    options?: { maxFiles?: number },
): {
    isDraggingOver: Ref<boolean>;
    onDragOver: (e: DragEvent) => void;
    onDragLeave: (e: DragEvent) => void;
    onDrop: (e: DragEvent) => void;
    onPaste: (e: ClipboardEvent) => void;
} {
    const maxFiles = options?.maxFiles ?? 5;
    const isDraggingOver = ref(false);
    let dragLeaveTimer: ReturnType<typeof setTimeout> | null = null;

    const appendIncoming = (incoming: File[]): void => {
        pendingFiles.value = mergeMessagingPendingAttachments(pendingFiles.value, incoming, maxFiles);
    };

    const clearDragLeaveTimer = (): void => {
        if (dragLeaveTimer !== null) {
            clearTimeout(dragLeaveTimer);
            dragLeaveTimer = null;
        }
    };

    const onDragOver = (e: DragEvent): void => {
        e.preventDefault();
        const types = e.dataTransfer?.types;
        if (!types || !Array.from(types).includes('Files')) {
            return;
        }
        if (e.dataTransfer) {
            e.dataTransfer.dropEffect = 'copy';
        }
        clearDragLeaveTimer();
        isDraggingOver.value = true;
    };

    const onDragLeave = (e: DragEvent): void => {
        const related = e.relatedTarget as Node | null;
        const current = e.currentTarget as HTMLElement;
        if (related && current.contains(related)) {
            return;
        }
        clearDragLeaveTimer();
        dragLeaveTimer = setTimeout(() => {
            isDraggingOver.value = false;
            dragLeaveTimer = null;
        }, 60);
    };

    const onDrop = (e: DragEvent): void => {
        e.preventDefault();
        clearDragLeaveTimer();
        isDraggingOver.value = false;
        appendIncoming(normalizeMessagingDroppedFiles(e.dataTransfer));
    };

    const onPaste = (e: ClipboardEvent): void => {
        const files = normalizeMessagingClipboardFiles(e);
        if (files.length === 0) {
            return;
        }
        e.preventDefault();
        appendIncoming(files);
    };

    return {
        isDraggingOver,
        onDragOver,
        onDragLeave,
        onDrop,
        onPaste,
    };
}
