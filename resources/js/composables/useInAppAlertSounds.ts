/**
 * In-app alert using `public/sound/tone.mp3`. Browsers may block audio until the user has
 * interacted with the page; {@link ensureInAppSoundsUnlocked} attaches a one-time gesture listener.
 */

const ALERT_TONE_URL = '/sound/tone.mp3';

let unlockListenerAttached = false;

const recentMessageSoundIds = new Map<number, number>();
const MESSAGE_DEDUPE_MS = 4000;

function pruneMessageIdMap(now: number): void {
    for (const [id, t] of recentMessageSoundIds) {
        if (now - t > MESSAGE_DEDUPE_MS) {
            recentMessageSoundIds.delete(id);
        }
    }
}

function shouldPlayMessageSound(messageId: number | undefined): boolean {
    const now = Date.now();
    pruneMessageIdMap(now);
    if (messageId == null) {
        return true;
    }
    if (recentMessageSoundIds.has(messageId)) {
        return false;
    }
    recentMessageSoundIds.set(messageId, now);
    return true;
}

function playToneFile(): void {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        const audio = new Audio(ALERT_TONE_URL);
        audio.volume = 0.9;
        void audio.play().catch(() => {});
    } catch {
        // ignore
    }
}

/**
 * Prime playback in a user-gesture context so later `play()` calls are more likely to succeed.
 */
export function ensureInAppSoundsUnlocked(): void {
    if (typeof document === 'undefined' || unlockListenerAttached) {
        return;
    }
    unlockListenerAttached = true;
    const unlock = (): void => {
        try {
            const audio = new Audio(ALERT_TONE_URL);
            audio.volume = 0.0001;
            void audio
                .play()
                .then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                })
                .catch(() => {});
        } catch {
            // ignore
        }
    };
    document.addEventListener('pointerdown', unlock, { once: true, passive: true });
    document.addEventListener('keydown', unlock, { once: true });
}

/** Plays `public/sound/tone.mp3` for in-app notifications. */
export function playNotificationSound(): void {
    playToneFile();
}

/**
 * Plays `public/sound/tone.mp3` for incoming chat messages. Pass `message.id` when available so
 * duplicate broadcasts (e.g. staff inbox + user channel) only play once.
 */
export function playMessageSound(messageId?: number): void {
    if (!shouldPlayMessageSound(messageId)) {
        return;
    }
    playToneFile();
}
