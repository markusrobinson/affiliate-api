<?php

namespace App\Enums;

enum AffiliateProvider: string
{
    case Walmart = 'walmart';
    case Amazon = 'amazon';

    public function label(): string
    {
        return match ($this) {
            self::Walmart => 'Walmart',
            self::Amazon => 'Amazon',
        };
    }

    /**
     * @return string[]
     */
    public function requiredCredentialKeys(): array
    {
        return match ($this) {
            self::Walmart => ['account_sid', 'auth_token'],
            self::Amazon => ['access_key', 'secret_key', 'partner_tag', 'marketplace'],
        };
    }
}
