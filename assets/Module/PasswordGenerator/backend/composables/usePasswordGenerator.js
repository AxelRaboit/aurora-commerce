import { reactive, ref, watch } from "vue";

const CHARS = {
    uppercase: "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
    lowercase: "abcdefghijklmnopqrstuvwxyz",
    digits: "0123456789",
    symbols: "!@#$%^&*()_+-=[]{}|;:,.<>?",
};

export function usePasswordGenerator() {
    const length = ref(16);
    const options = reactive({
        uppercase: true,
        lowercase: true,
        digits: true,
        symbols: false,
    });
    const password = ref("");
    const copied = ref(false);

    function charset() {
        return Object.entries(CHARS)
            .filter(([key]) => options[key])
            .map(([, chars]) => chars)
            .join("");
    }

    function entropy() {
        const chars = charset();
        if (!chars || !length.value) return 0;
        return Math.floor(length.value * Math.log2(chars.length));
    }

    function generate() {
        const chars = charset();
        if (!chars) {
            password.value = "";
            return;
        }

        const array = new Uint32Array(length.value);
        crypto.getRandomValues(array);
        password.value = Array.from(array, (n) => chars[n % chars.length]).join(
            "",
        );
        copied.value = false;
    }

    async function copy() {
        if (!password.value) return;
        await navigator.clipboard.writeText(password.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }

    watch([length, options], generate, { immediate: true, deep: true });

    return { length, options, password, copied, entropy, generate, copy };
}
