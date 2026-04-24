import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "assets"),
        },
    },
    test: {
        environment: "jsdom",
        globals: true,
        include: ["assets/**/*.{test,spec}.{js,ts}"],
        exclude: ["node_modules", "tests/e2e/**", "dist/**"],
        env: {
            TZ: "UTC",
        },
    },
});
