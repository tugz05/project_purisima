import { nextTick } from 'vue';

/**
 * Scroll a messaging pane to the latest message after layout paints.
 * Uses rAF + optional delayed passes for flex layouts, CSS transitions, and images.
 */
export function scheduleScrollToBottom(
    scrollEl: HTMLElement | null | undefined,
    endAnchor?: HTMLElement | null | undefined,
    extraDelaysMs: number[] = [80, 200],
): void {
    const apply = (): void => {
        if (scrollEl) {
            scrollEl.scrollTop = scrollEl.scrollHeight;
        }
        endAnchor?.scrollIntoView({ block: 'end', behavior: 'auto' });
    };

    nextTick(() => {
        requestAnimationFrame(() => {
            requestAnimationFrame(apply);
        });
    });

    for (const ms of extraDelaysMs) {
        setTimeout(apply, ms);
    }
}
