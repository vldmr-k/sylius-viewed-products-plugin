<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Enum;

/**
 * Where the list of viewed products is kept (config "storage.type").
 */
enum StorageType: string
{
    /** In the session: a visitor who views a product gets a session. */
    case Session = 'session';

    /** In a cookie of its own: viewing products does not start a session. */
    case Cookie = 'cookie';

    public function serviceId(): string
    {
        return sprintf('vldmrk.viewed_product.storage.%s', $this->value);
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
