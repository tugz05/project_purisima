import { getPusher, isPusherAvailable } from '@/pusher';

export type MessageSentPayload = {
    message: {
        id: number;
        content: string;
        type?: string;
        attachments?: unknown;
        is_read?: boolean;
        is_edited?: boolean;
        created_at: string;
        display_time?: string;
        sender: {
            id: number;
            name: string;
            email?: string;
            role?: string;
            photo_url?: string | null;
        };
    };
    conversation: {
        id: number;
        last_message?: string | null;
        last_message_at?: string | null;
        resident_has_unread?: boolean;
        staff_has_unread?: boolean;
    };
    /** Present when a resident sent a message (shared staff inbox). */
    staff_messaging_unread_total?: number | null;
    /** Present when staff sent; resident should apply if recipient_user_id matches. */
    recipient_user_id?: number | null;
    recipient_messaging_unread_total?: number | null;
};

export type UserTypingPayload = {
    user: { id: number; name: string; photo_url?: string | null };
    is_typing: boolean;
    conversation_id: number;
};

/**
 * Subscribe to a conversation private channel (realtime messages + typing).
 */
export function subscribeToConversationChannel(
    conversationId: number,
    handlers: {
        onMessageSent: (payload: MessageSentPayload) => void;
        onUserTyping: (payload: UserTypingPayload) => void;
    },
): () => void {
    const pusher = getPusher();
    if (!pusher || !isPusherAvailable()) {
        return () => {};
    }

    const name = `private-conversation.${conversationId}`;
    const channel = pusher.subscribe(name);

    const onMessage = (e: MessageSentPayload) => handlers.onMessageSent(e);
    const onTyping = (e: UserTypingPayload) => handlers.onUserTyping(e);

    channel.bind('message.sent', onMessage);
    channel.bind('user.typing', onTyping);

    return () => {
        try {
            channel.unbind('message.sent', onMessage);
            channel.unbind('user.typing', onTyping);
            pusher.unsubscribe(name);
        } catch {
            // ignore
        }
    };
}

/**
 * Shared staff inbox: all staff receive resident-originated messages for badges and list sync.
 */
export function subscribeToStaffMessagingInboxChannel(handlers: {
    onMessageSent: (payload: MessageSentPayload) => void;
}): () => void {
    const pusher = getPusher();
    if (!pusher || !isPusherAvailable()) {
        return () => {};
    }

    const name = 'private-messaging.staff';
    const channel = pusher.subscribe(name);
    const onMessage = (e: MessageSentPayload) => handlers.onMessageSent(e);
    channel.bind('message.sent', onMessage);

    return () => {
        try {
            channel.unbind('message.sent', onMessage);
            pusher.unsubscribe(name);
        } catch {
            // ignore
        }
    };
}

/**
 * Subscribe to the Laravel user private channel (badge + sidebar when not in that conversation).
 */
export function subscribeToUserMessagingChannel(
    userId: number,
    handlers: {
        onMessageSent: (payload: MessageSentPayload) => void;
    },
): () => void {
    const pusher = getPusher();
    if (!pusher || !isPusherAvailable()) {
        return () => {};
    }

    const name = `private-App.Models.User.${userId}`;
    const channel = pusher.subscribe(name);

    const onMessage = (e: MessageSentPayload) => handlers.onMessageSent(e);
    channel.bind('message.sent', onMessage);

    return () => {
        try {
            channel.unbind('message.sent', onMessage);
            pusher.unsubscribe(name);
        } catch {
            // ignore
        }
    };
}
