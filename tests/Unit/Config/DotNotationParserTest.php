<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Elarion\Config\DotNotationParser;
use PHPUnit\Framework\TestCase;

final class DotNotationParserTest extends TestCase
{
    private DotNotationParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DotNotationParser();
    }

    public function test_parse_splits_key_by_dots(): void
    {
        $result = $this->parser->parse('app.name.first');
        $this->assertEquals(['app', 'name', 'first'], $result);
    }

    public function test_get_returns_nested_value(): void
    {
        $data = ['app' => ['name' => 'ElarionStack']];
        $result = $this->parser->get($data, 'app.name');
        $this->assertEquals('ElarionStack', $result);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        $data = ['app' => ['name' => 'Test']];
        $result = $this->parser->get($data, 'app.missing', 'default');
        $this->assertEquals('default', $result);
    }

    public function test_set_creates_nested_structure(): void
    {
        $data = [];
        $this->parser->set($data, 'app.name', 'ElarionStack');
        $this->assertEquals(['app' => ['name' => 'ElarionStack']], $data);
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        $data = ['app' => ['name' => 'Test']];
        $this->assertTrue($this->parser->has($data, 'app.name'));
    }

    public function test_has_returns_false_for_missing_key(): void
    {
        $data = ['app' => ['name' => 'Test']];
        $this->assertFalse($this->parser->has($data, 'app.missing'));
    }
}
