import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export function moduleAlias(name) {
    return path.resolve(__dirname, `src/Module/${name}/assets`);
}

export const aliases = {
    "@": path.resolve(__dirname, "src/Core/Frontend"),
    "@core": path.resolve(__dirname, "src/Core/Frontend"),
    "@shared": path.resolve(__dirname, "src/Core/Frontend/shared"),
    "@platform": moduleAlias("Platform"),
    "@configuration": moduleAlias("Configuration"),
    "@media": moduleAlias("Media"),
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
    "@vault": moduleAlias("Vault"),
    "@password-generator": moduleAlias("PasswordGenerator"),
    "@personal-finance": moduleAlias("PersonalFinance"),
};
