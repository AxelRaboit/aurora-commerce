import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export function moduleAlias(name) {
    return path.resolve(__dirname, `assets/Module/${name}`);
}

export const aliases = {
    "@": path.resolve(__dirname, "assets"),
    "@core": path.resolve(__dirname, "assets/Core"),
    "@shared": path.resolve(__dirname, "assets/shared"),
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
    "@vault": moduleAlias("Vault"),
    "@password-generator": moduleAlias("PasswordGenerator"),
};
