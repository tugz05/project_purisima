<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    downloadMessagingAttachment,
    isImageAttachment,
    messagingAttachmentPublicUrl,
    openMessagingAttachmentInNewTab,
    type MessagingAttachment,
} from '@/utils/messagingAttachments';
import { Download, ExternalLink, FileText, Image as ImageIcon } from 'lucide-vue-next';

const props = defineProps<{
    attachments: MessagingAttachment[] | null | undefined;
    tone: 'staff-outgoing' | 'resident-outgoing' | 'incoming';
}>();

const fileRowClass = () => {
    const map: Record<typeof props.tone, string> = {
        'staff-outgoing':
            'flex flex-wrap items-center gap-2 rounded-lg border border-white/20 bg-white/15 px-2 py-2 text-white',
        'resident-outgoing':
            'flex flex-wrap items-center gap-2 rounded-lg border border-white/25 bg-white/20 px-2 py-2 text-white',
        incoming: 'flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-2 py-2 text-gray-900',
    };

    return map[props.tone];
};

const subtleText = () => {
    const map: Record<typeof props.tone, string> = {
        'staff-outgoing': 'text-white/80',
        'resident-outgoing': 'text-white/85',
        incoming: 'text-gray-600',
    };

    return map[props.tone];
};

const iconBtnClass = () => {
    const map: Record<typeof props.tone, string> = {
        'staff-outgoing': 'text-white hover:bg-white/20',
        'resident-outgoing': 'text-white hover:bg-white/25',
        incoming: 'text-gray-700 hover:bg-gray-200',
    };

    return map[props.tone];
};
</script>

<template>
    <div v-if="attachments && attachments.length > 0" class="mt-2 space-y-2">
        <template v-for="attachment in attachments" :key="`${attachment.path}-${attachment.name}`">
            <div
                v-if="isImageAttachment(attachment)"
                class="overflow-hidden rounded-lg border shadow-sm"
                :class="
                    tone === 'incoming' ? 'border-gray-200 bg-white' : 'border-white/25 bg-black/10'
                "
            >
                <a
                    :href="messagingAttachmentPublicUrl(attachment.path)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block"
                >
                    <img
                        :src="messagingAttachmentPublicUrl(attachment.path)"
                        :alt="attachment.name"
                        class="max-h-56 w-full object-contain"
                        loading="lazy"
                    />
                </a>
                <div
                    class="flex flex-wrap items-center gap-1 border-t px-2 py-1.5"
                    :class="tone === 'incoming' ? 'border-gray-200 bg-gray-50' : 'border-white/20 bg-black/20'"
                >
                    <ImageIcon class="h-3.5 w-3.5 shrink-0 opacity-80" />
                    <span class="min-w-0 flex-1 truncate text-xs" :class="subtleText()">{{ attachment.name }}</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        :class="iconBtnClass()"
                        @click.prevent="openMessagingAttachmentInNewTab(attachment)"
                    >
                        <ExternalLink class="mr-1 h-3 w-3" />
                        View
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        :class="iconBtnClass()"
                        @click.prevent="downloadMessagingAttachment(attachment)"
                    >
                        <Download class="mr-1 h-3 w-3" />
                        Save
                    </Button>
                </div>
            </div>

            <div
                v-else
                class="flex flex-wrap items-center gap-2 rounded-lg border px-2 py-2"
                :class="fileRowClass()"
            >
                <FileText class="h-4 w-4 shrink-0 opacity-90" />
                <span class="min-w-0 flex-1 truncate text-xs font-medium">{{ attachment.name }}</span>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 shrink-0 px-2"
                    :class="iconBtnClass()"
                    @click.prevent="openMessagingAttachmentInNewTab(attachment)"
                >
                    <ExternalLink class="mr-1 h-3 w-3" />
                    <span class="text-xs">View</span>
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 shrink-0 px-2"
                    :class="iconBtnClass()"
                    @click.prevent="downloadMessagingAttachment(attachment)"
                >
                    <Download class="mr-1 h-3 w-3" />
                    <span class="text-xs">Download</span>
                </Button>
            </div>
        </template>
    </div>
</template>
