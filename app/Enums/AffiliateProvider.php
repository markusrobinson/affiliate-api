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
            self::Walmart => ['consumer_id', 'private_key', 'channel_type'],
            self::Amazon => ['access_key', 'secret_key', 'partner_tag', 'marketplace'],
        };
    }
}
