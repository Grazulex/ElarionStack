<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Elarion\Core\Application;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour la classe Application
 */
class ApplicationTest extends TestCase
{
    public function test_can_create_application(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $this->assertInstanceOf(Application::class, $app);
    }

    public function test_returns_correct_version(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $this->assertSame('0.1.0-dev', $app->version());
    }

    public function test_returns_base_path(): void
    {
        $basePath = __DIR__ . '/../../..';
        $app = new Application($basePath);

        $this->assertSame($basePath, $app->basePath());
    }

    public function test_returns_path_relative_to_base(): void
    {
        $basePath = __DIR__ . '/../../..';
        $app = new Application($basePath);

        $this->assertSame(
            $basePath . '/src',
            $app->basePath('src')
        );
    }

    public function test_normalizes_paths(): void
    {
        $basePath = __DIR__ . '/../../..';
        $app = new Application($basePath);

        // Teste avec un slash au début
        $this->assertSame(
            $basePath . '/src',
            $app->basePath('/src')
        );
    }
}
