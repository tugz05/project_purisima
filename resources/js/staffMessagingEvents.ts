/** Dispatched when staff messaging unread count changes (any page can listen). */
export const STAFF_MESSAGING_UNREAD_EVENT = 'staff-messaging-unread';

export function dispatchStaffMessagingUnreadCount(count: number): void {
    if (typeof window === 'undefined') {
        return;
    }
    window.dispatchEvent(new CustomEvent(STAFF_MESSAGING_UNREAD_EVENT, { detail: { count } }));
}
