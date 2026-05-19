import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import { aliases } from "./aliases.js";

export default defineConfig({
    plugins: [vue()],
    resolve: { alias: aliases },
    test: {
        environment: "jsdom",
        globals: true,
        include: ["src/**/*.{test,spec}.{js,ts}"],
        exclude: ["node_modules", "tests/e2e/**", "dist/**"],
        pool: "threads",
        minThreads: 1,
        maxThreads: 4,
        testTimeout: 30000,
        env: {
            TZ: "UTC",
        },
    },
});
