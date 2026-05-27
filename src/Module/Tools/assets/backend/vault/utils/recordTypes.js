import {
    KeyRound,
    CreditCard,
    IdCard,
    NotebookPen,
    Landmark,
    Terminal,
    BookOpen,
    Car,
    Mail,
    Database,
    Server,
    Code,
    Bitcoin,
    Wifi,
    Package,
} from "lucide-vue-next";

/** Maps record type icon slugs to Lucide components. */
export const ICONS = {
    "key-round": KeyRound,
    "credit-card": CreditCard,
    "id-card": IdCard,
    "notebook-pen": NotebookPen,
    landmark: Landmark,
    terminal: Terminal,
    "book-open": BookOpen,
    car: Car,
    mail: Mail,
    database: Database,
    server: Server,
    code: Code,
    bitcoin: Bitcoin,
    wifi: Wifi,
    package: Package,
};

export const RECORD_TYPES = [
    {
        value: "login",
        icon: "key-round",
        fields: ["username", "password", "url", "notes"],
    },
    {
        value: "card",
        icon: "credit-card",
        fields: [
            "cardHolder",
            "cardNumber",
            "expiryDate",
            "cvv",
            "pin",
            "notes",
        ],
    },
    {
        value: "identity",
        icon: "id-card",
        fields: [
            "firstName",
            "lastName",
            "birthDate",
            "email",
            "phone",
            "address",
            "notes",
        ],
    },
    { value: "secure_note", icon: "notebook-pen", fields: ["notes"] },
    {
        value: "bank_account",
        icon: "landmark",
        fields: ["bankName", "iban", "bic", "pin", "notes"],
    },
    {
        value: "ssh_key",
        icon: "terminal",
        fields: ["publicKey", "privateKey", "passphrase", "notes"],
    },
    {
        value: "passport",
        icon: "book-open",
        fields: [
            "firstName",
            "lastName",
            "passportNumber",
            "issuer",
            "expiryDate",
            "notes",
        ],
    },
    {
        value: "driver_license",
        icon: "car",
        fields: [
            "firstName",
            "lastName",
            "licenseNumber",
            "issuer",
            "expiryDate",
            "notes",
        ],
    },
    { value: "email", icon: "mail", fields: ["email", "password", "notes"] },
    {
        value: "database",
        icon: "database",
        fields: ["host", "port", "database", "username", "password", "notes"],
    },
    {
        value: "server",
        icon: "server",
        fields: ["host", "port", "username", "password", "notes"],
    },
    {
        value: "api_key",
        icon: "code",
        fields: ["apiKey", "apiSecret", "notes"],
    },
    {
        value: "crypto_wallet",
        icon: "bitcoin",
        fields: ["walletAddress", "seedPhrase", "notes"],
    },
    {
        value: "wifi_password",
        icon: "wifi",
        fields: ["ssid", "wifiPassword", "notes"],
    },
    {
        value: "software_license",
        icon: "package",
        fields: ["licenseKey", "notes"],
    },
];

/**
 * Fields rendered as password inputs (masked •••••••• with toggle) in forms,
 * and displayed as •••••••• with toggle in the view modal.
 */
export const PASSWORD_FIELDS = [
    "password",
    "passphrase",
    "cvv",
    "pin",
    "wifiPassword",
    "cardNumber",
    "iban",
    "apiKey",
    "apiSecret",
    "licenseKey",
    "passportNumber",
    "licenseNumber",
];

/**
 * Fields rendered as monospace textareas in forms,
 * and displayed blurred (blur-sm) with toggle in the view modal.
 * Used for long secrets that don't fit on a single line.
 */
export const TEXTAREA_FIELDS = ["privateKey", "publicKey", "seedPhrase"];

export function getRecordType(value) {
    return RECORD_TYPES.find((type) => type.value === value) ?? RECORD_TYPES[0];
}

export function emptyFieldsForType(typeValue) {
    const recordType = getRecordType(typeValue);
    return Object.fromEntries(recordType.fields.map((field) => [field, ""]));
}
