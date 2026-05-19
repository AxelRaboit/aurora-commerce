const isDev = window.__isDev__ === true;
const isAdmin = window.__isAdmin__ === true;
const privileges = new Set(
    Array.isArray(window.__privileges__) ? window.__privileges__ : [],
);

/**
 * Returns whether the current user can perform an action identified by a privilege string.
 *
 * Rules mirror the server-side ModulePermissionVoter:
 *   - Dev  → always true
 *   - Admin → always true
 *   - User  → only if the privilege is in their explicit list
 */
export function usePrivileges() {
    function can(privilege) {
        if (isDev || isAdmin) return true;
        return privileges.has(privilege);
    }

    return { can, isDev, isAdmin };
}
