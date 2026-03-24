/**
 * Prefer stored profile / OAuth photo URL; fall back to ui-avatars.com initials.
 */
export function userAvatarUrl(
    name: string,
    photoUrl: string | null | undefined,
    options?: { background?: string },
): string {
    const bg = options?.background ?? '64748b';
    const trimmed = typeof photoUrl === 'string' ? photoUrl.trim() : '';
    if (trimmed !== '') {
        return trimmed;
    }
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=${bg}&color=fff&bold=true`;
}
