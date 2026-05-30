import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export function moduleAlias(name) {
    return path.resolve(__dirname, `src/Module/${name}/assets`);
}

export const aliases = {
    "@": path.resolve(__dirname, "src/Core/assets"),
    "@core": path.resolve(__dirname, "src/Core/assets"),
    "@shared": path.resolve(__dirname, "src/Core/assets/shared"),
    "@platform": moduleAlias("Platform"),
    "@configuration": moduleAlias("Configuration"),
    "@general": moduleAlias("General"),
    "@dev": moduleAlias("Dev"),
    "@editorial": moduleAlias("Editorial"),
    "@crm": moduleAlias("Crm"),
    "@erp": moduleAlias("Erp"),
    "@ecommerce": moduleAlias("Ecommerce"),
    "@photo": moduleAlias("Photo"),
    "@billing": moduleAlias("Billing"),
    "@ged": moduleAlias("Ged"),
    "@hr": moduleAlias("Hr"),
    "@planning": moduleAlias("Planning"),
    "@project": moduleAlias("Project"),
    "@notes": moduleAlias("Notes"),
    "@assistant": moduleAlias("Assistant"),
    "@tools": moduleAlias("Tools"),
    "@personal-finance": moduleAlias("PersonalFinance"),
};
