import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "assets"),
            "@core": path.resolve(__dirname, "assets/Core"),
            "@editorial": path.resolve(__dirname, "assets/Module/Editorial"),
            "@crm": path.resolve(__dirname, "assets/Module/Crm"),
            "@erp": path.resolve(__dirname, "assets/Module/Erp"),
            "@ecommerce": path.resolve(__dirname, "assets/Module/Ecommerce"),
            "@photo": path.resolve(__dirname, "assets/Module/Photo"),
            "@billing": path.resolve(__dirname, "assets/Module/Billing"),
            "@shared": path.resolve(__dirname, "assets/shared"),
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
