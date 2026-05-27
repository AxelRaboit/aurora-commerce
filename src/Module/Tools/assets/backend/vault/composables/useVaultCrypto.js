/**
 * End-to-end encryption for the vault.
 *
 * Key derivation : PBKDF2-SHA256 (600 000 iterations) — NIST SP 800-132 compliant.
 * Encryption     : AES-256-GCM (authenticated, 96-bit IV per entry).
 * Salt           : 128-bit random, generated once per user, stored server-side.
 *
 * Session persistence (optional, user-controlled):
 *   The derived CryptoKey is exported as JWK and stored in sessionStorage with
 *   an expiry timestamp. sessionStorage is tab-scoped and cleared on tab close.
 *   Security model: same XSS surface as keeping the key in memory, but survives
 *   page reloads within the same tab — identical to Bitwarden's web vault approach.
 */

const PBKDF2_ITERATIONS = 600_000;
const KEY_LENGTH_BITS = 256;
const SESSION_STORAGE_KEY = "aurora_vault_session";

function base64Encode(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}

function base64Decode(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

export function generateSalt() {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    return base64Encode(salt.buffer);
}

async function deriveKey(masterPassword, saltBase64) {
    const encoder = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
        "raw",
        encoder.encode(masterPassword),
        { name: "PBKDF2" },
        false,
        ["deriveKey"],
    );

    return crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt: base64Decode(saltBase64),
            iterations: PBKDF2_ITERATIONS,
            hash: "SHA-256",
        },
        keyMaterial,
        { name: "AES-GCM", length: KEY_LENGTH_BITS },
        true, // extractable — required for session persistence via sessionStorage
        ["encrypt", "decrypt"],
    );
}

export function useVaultCrypto() {
    let derivedKey = null;

    async function unlock(masterPassword, saltBase64) {
        try {
            derivedKey = await deriveKey(masterPassword, saltBase64);
            return true;
        } catch {
            return false;
        }
    }

    function lock() {
        derivedKey = null;
        clearSession();
    }

    function isUnlocked() {
        return derivedKey !== null;
    }

    async function persist(durationMinutes) {
        if (!derivedKey) return;
        try {
            const exported = await crypto.subtle.exportKey("jwk", derivedKey);
            const expiry =
                durationMinutes > 0
                    ? Date.now() + durationMinutes * 60 * 1000
                    : null;
            sessionStorage.setItem(
                SESSION_STORAGE_KEY,
                JSON.stringify({ key: exported, expiry }),
            );
        } catch {
            // sessionStorage unavailable or exportKey failed
        }
    }

    async function restoreFromSession() {
        try {
            const stored = sessionStorage.getItem(SESSION_STORAGE_KEY);
            if (!stored) return false;

            const { key, expiry } = JSON.parse(stored);
            if (expiry !== null && Date.now() > expiry) {
                sessionStorage.removeItem(SESSION_STORAGE_KEY);
                return false;
            }

            derivedKey = await crypto.subtle.importKey(
                "jwk",
                key,
                { name: "AES-GCM", length: KEY_LENGTH_BITS },
                true,
                ["encrypt", "decrypt"],
            );
            return true;
        } catch {
            sessionStorage.removeItem(SESSION_STORAGE_KEY);
            return false;
        }
    }

    function clearSession() {
        sessionStorage.removeItem(SESSION_STORAGE_KEY);
    }

    function sessionExpiry() {
        try {
            const stored = sessionStorage.getItem(SESSION_STORAGE_KEY);
            if (!stored) return null;
            const { expiry } = JSON.parse(stored);
            return expiry;
        } catch {
            return null;
        }
    }

    async function encrypt(fields) {
        if (!derivedKey) throw new Error("Vault is locked");

        const encoder = new TextEncoder();
        const iv = crypto.getRandomValues(new Uint8Array(12));

        const ciphertext = await crypto.subtle.encrypt(
            { name: "AES-GCM", iv },
            derivedKey,
            encoder.encode(JSON.stringify(fields)),
        );

        return {
            encryptedData: base64Encode(ciphertext),
            iv: base64Encode(iv.buffer),
        };
    }

    async function decrypt(encryptedData, ivBase64) {
        if (!derivedKey) throw new Error("Vault is locked");

        const decoder = new TextDecoder();
        const plaintext = await crypto.subtle.decrypt(
            { name: "AES-GCM", iv: base64Decode(ivBase64) },
            derivedKey,
            base64Decode(encryptedData),
        );

        return JSON.parse(decoder.decode(plaintext));
    }

    async function verifyPassword(masterPassword, saltBase64, sampleEntry) {
        try {
            const testKey = await deriveKey(masterPassword, saltBase64);
            const decoder = new TextDecoder();
            await crypto.subtle.decrypt(
                { name: "AES-GCM", iv: base64Decode(sampleEntry.iv) },
                testKey,
                base64Decode(sampleEntry.encryptedData),
            );
            return true;
        } catch {
            return false;
        }
    }

    return {
        unlock,
        lock,
        isUnlocked,
        encrypt,
        decrypt,
        verifyPassword,
        persist,
        restoreFromSession,
        sessionExpiry,
    };
}
