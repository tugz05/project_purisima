/**
 * Client-side filters aligned with messaging send validation (jpg, png, pdf, doc, docx).
 */
export function isMessagingAllowedFile(file: File): boolean {
    const mime = file.type.toLowerCase();
    if (
        mime === 'image/jpeg' ||
        mime === 'image/jpg' ||
        mime === 'image/png' ||
        mime === 'application/pdf' ||
        mime === 'application/msword' ||
        mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ) {
        return true;
    }

    return /\.(jpe?g|png|pdf|doc|docx)$/i.test(file.name);
}

export function mergeMessagingPendingAttachments(current: File[], incoming: File[], max: number): File[] {
    const allowed = incoming.filter(isMessagingAllowedFile);
    const room = max - current.length;
    if (room <= 0) {
        return current;
    }

    return [...current, ...allowed.slice(0, room)];
}

export function normalizeMessagingDroppedFiles(dataTransfer: DataTransfer | null): File[] {
    if (!dataTransfer?.files?.length) {
        return [];
    }

    return Array.from(dataTransfer.files);
}

function fileDedupeKey(file: File): string {
    return `${file.name}\0${file.size}\0${file.type}`;
}

/**
 * Files from paste (e.g. screenshots, copied files from Explorer/Finder).
 */
export function normalizeMessagingClipboardFiles(event: ClipboardEvent): File[] {
    const out: File[] = [];
    const seen = new Set<string>();
    const cd = event.clipboardData;
    if (!cd) {
        return out;
    }

    const push = (f: File | null): void => {
        if (!f || !isMessagingAllowedFile(f)) {
            return;
        }
        const k = fileDedupeKey(f);
        if (seen.has(k)) {
            return;
        }
        seen.add(k);
        out.push(f);
    };

    if (cd.files?.length) {
        for (let i = 0; i < cd.files.length; i++) {
            push(cd.files[i] ?? null);
        }
    }

    const items = cd.items;
    if (items) {
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if (item?.kind !== 'file') {
                continue;
            }
            push(item.getAsFile());
        }
    }

    return out;
}
