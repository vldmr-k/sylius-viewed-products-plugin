<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Vldmrk\SyliusViewedProductsPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testTheDefaultsAreASessionOfTwentyAndAThirtyDayCookie(): void
    {
        self::assertSame([
            'storage' => [
                'type' => 'session',
                'limit' => 20,
                'cookie' => ['name' => 'viewed_products', 'lifetime' => 2592000],
            ],
        ], $this->process([]));
    }

    public function testTheHostConfigsAreAccepted(): void
    {
        // pmpro and inked config/packages/vldmrk_sylius_viewed_products.y(a)ml
        self::assertSame(50, $this->process([['storage' => ['limit' => 50]]])['storage']['limit']);

        $inked = $this->process([['storage' => ['type' => 'cookie', 'cookie' => ['name' => 'viewed_products', 'lifetime' => 2592000]]]]);
        self::assertSame('cookie', $inked['storage']['type']);
        self::assertSame(20, $inked['storage']['limit']);
    }

    /**
     * @dataProvider invalidConfigs
     */
    public function testAnInvalidConfigIsRejected(array $storage): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([['storage' => $storage]]);
    }

    public static function invalidConfigs(): array
    {
        return [
            'unknown type' => [['type' => 'redis']],
            'zero limit' => [['limit' => 0]],
            'string limit' => [['limit' => 'many']],
            'empty cookie name' => [['cookie' => ['name' => '']]],
            'zero lifetime' => [['cookie' => ['lifetime' => 0]]],
            'unknown key' => [['ttl' => 5]],
        ];
    }

    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
