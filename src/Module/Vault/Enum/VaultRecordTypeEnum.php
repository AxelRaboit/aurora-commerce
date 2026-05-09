<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\Enum;

enum VaultRecordTypeEnum: string
{
    case Login = 'login';
    case Card = 'card';
    case Identity = 'identity';
    case SecureNote = 'secure_note';
    case BankAccount = 'bank_account';
    case SshKey = 'ssh_key';
    case Passport = 'passport';
    case DriverLicense = 'driver_license';
    case Email = 'email';
    case Database = 'database';
    case Server = 'server';
    case ApiKey = 'api_key';
    case CryptoWallet = 'crypto_wallet';
    case WifiPassword = 'wifi_password';
    case SoftwareLicense = 'software_license';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'vault.types.login',
            self::Card => 'vault.types.card',
            self::Identity => 'vault.types.identity',
            self::SecureNote => 'vault.types.secure_note',
            self::BankAccount => 'vault.types.bank_account',
            self::SshKey => 'vault.types.ssh_key',
            self::Passport => 'vault.types.passport',
            self::DriverLicense => 'vault.types.driver_license',
            self::Email => 'vault.types.email',
            self::Database => 'vault.types.database',
            self::Server => 'vault.types.server',
            self::ApiKey => 'vault.types.api_key',
            self::CryptoWallet => 'vault.types.crypto_wallet',
            self::WifiPassword => 'vault.types.wifi_password',
            self::SoftwareLicense => 'vault.types.software_license',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Login => 'key-round',
            self::Card => 'credit-card',
            self::Identity => 'id-card',
            self::SecureNote => 'notebook-pen',
            self::BankAccount => 'landmark',
            self::SshKey => 'terminal',
            self::Passport => 'book-open',
            self::DriverLicense => 'car',
            self::Email => 'mail',
            self::Database => 'database',
            self::Server => 'server',
            self::ApiKey => 'code',
            self::CryptoWallet => 'bitcoin',
            self::WifiPassword => 'wifi',
            self::SoftwareLicense => 'package',
        };
    }
}
