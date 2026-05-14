import { createApp } from "vue";
import AppThemeToggle from "@/shared/components/action/AppThemeToggle.vue";

const mountPoint = document.getElementById("guest-toggle");
if (mountPoint) createApp(ThemeToggle).mount(mountPoint);
