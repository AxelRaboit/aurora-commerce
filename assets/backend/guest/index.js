import { createApp } from "vue";
import ThemeToggle from "@/shared/components/action/ThemeToggle.vue";

const mountPoint = document.getElementById("guest-toggle");
if (mountPoint) createApp(ThemeToggle).mount(mountPoint);
