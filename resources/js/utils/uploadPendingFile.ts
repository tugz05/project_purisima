import { laravelJsonFetch } from '@/utils/laravelJsonFetch';

export type PendingUploadPurpose = 'transaction_submission' | 'payment_proof';

export interface PendingUploadResult {
    id: string;
    original_name: string;
    size: number;
    mime_type: string;
}

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * Upload a single file immediately (XHR + progress). Final forms submit only metadata + upload IDs.
 */
export function uploadPendingFile(
    file: File,
    purpose: PendingUploadPurpose,
    onProgress?: (percent: number) => void,
): Promise<PendingUploadResult> {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('purpose', purpose);
        formData.append('file', file);

        xhr.open('POST', '/uploads/pending');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable && onProgress) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const body = JSON.parse(xhr.responseText) as PendingUploadResult;
                    if (!body.id) {
                        reject(new Error('Invalid upload response'));
                        return;
                    }
                    resolve(body);
                } catch {
                    reject(new Error('Invalid upload response'));
                }
                return;
            }
            let message = `Upload failed (${xhr.status})`;
            try {
                const err = JSON.parse(xhr.responseText) as { message?: string; errors?: Record<string, string[]> };
                if (err.message) {
                    message = err.message;
                } else if (err.errors) {
                    message = Object.values(err.errors).flat().join(' ') || message;
                }
            } catch {
                /* keep default */
            }
            reject(new Error(message));
        };

        xhr.onerror = () => reject(new Error('Network error during upload'));

        xhr.send(formData);
    });
}

export async function deletePendingUpload(id: string): Promise<void> {
    const response = await laravelJsonFetch(`/uploads/pending/${id}`, {
        method: 'DELETE',
    });
    if (!response.ok) {
        throw new Error('Failed to remove upload');
    }
}
