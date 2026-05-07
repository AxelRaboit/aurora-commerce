import { ref } from "vue";

export function useThemesList(initialThemes) {
    const themeList = ref(initialThemes.map((theme) => ({ ...theme })));

    function accentColor(theme) {
        return (
            theme.config?.["primary_color"] ??
            theme.config?.["--th-accent"] ??
            "#6366f1"
        );
    }

    return { themeList, accentColor };
}
