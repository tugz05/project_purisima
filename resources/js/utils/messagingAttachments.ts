import { messagingJsonFetch } from '@/utils/messagingHttp';

export interface MessagingAttachment {
    name: string;
    path: string;
    mime_type?: string;
    size?: number;
}

export const MESSAGING_QUICK_EMOJIS: string[] = [
    '😀',
    '😂',
    '😊',
    '🙏',
    '❤️',
    '👍',
    '👎',
    '👋',
    '🎉',
    '✅',
    '❌',
    '🔔',
    '📎',
    '📷',
    '🏠',
    '⚠️',
    '💬',
    '🙌',
    '🤝',
    '☀️',
    '🌧️',
    '⏰',
    '📄',
    '📝',
];

export function messagingAttachmentPublicUrl(path: string): string {
    const normalized = path.replace(/^\/+/, '');

    return `/storage/${normalized}`;
}

export function isImageAttachment(attachment: Pick<MessagingAttachment, 'mime_type' | 'name'>): boolean {
    const mime = attachment.mime_type ?? '';
    if (mime.startsWith('image/')) {
        return true;
    }

    return /\.(jpe?g|png|gif|webp)$/i.test(attachment.name ?? '');
}

export function isPdfAttachment(attachment: Pick<MessagingAttachment, 'mime_type' | 'name'>): boolean {
    const mime = attachment.mime_type ?? '';
    if (mime === 'application/pdf') {
        return true;
    }

    return /\.pdf$/i.test(attachment.name ?? '');
}

export function openMessagingAttachmentInNewTab(attachment: MessagingAttachment): void {
    window.open(messagingAttachmentPublicUrl(attachment.path), '_blank', 'noopener,noreferrer');
}

export function downloadMessagingAttachment(attachment: MessagingAttachment): void {
    const link = document.createElement('a');
    link.href = messagingAttachmentPublicUrl(attachment.path);
    link.download = attachment.name;
    link.rel = 'noopener';
    link.click();
}

/**
 * POST a conversation message with JSON or multipart (when files are present).
 */
export async function postConversationMessage(
    url: string,
    payload: { content: string; files: File[] },
): Promise<Response> {
    if (payload.files.length === 0) {
        return messagingJsonFetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ content: payload.content }),
        });
    }

    const formData = new FormData();
    formData.append('content', payload.content);
    payload.files.forEach((file) => {
        formData.append('attachments[]', file);
    });

    return messagingJsonFetch(url, {
        method: 'POST',
        body: formData,
    });
}
