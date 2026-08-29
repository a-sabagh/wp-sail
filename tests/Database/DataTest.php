<?php

namespace WPSail\Tests\Database;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPSail\Database\Data;

final class DataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StaticData::reset_find_calls();
    }

    public function test_find_is_an_abstract_static_method(): void
    {
        $method = new ReflectionMethod(Data::class, 'find');

        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    public function test_find_can_be_called_statically(): void
    {
        $record = StaticData::find(7);

        $this->assertInstanceOf(StaticData::class, $record);
        $this->assertSame(['name' => 'Sail'], $record->get_data());
        $this->assertSame([7], StaticData::$find_calls);
    }

    public function test_constructor_uses_the_static_find_implementation(): void
    {
        $record = new StaticData(7);

        $this->assertSame(7, $record->get_id());
        $this->assertSame(['name' => 'Sail'], $record->get_data());
        $this->assertSame([7], StaticData::$find_calls);
    }
}

final class StaticData extends Data
{
    public static array $find_calls = [];

    protected array $data = [
        'name' => null,
    ];

    public static function reset_find_calls(): void
    {
        self::$find_calls = [];
    }

    public static function find(int $id): ?self
    {
        self::$find_calls[] = $id;

        if (7 !== $id) {
            return null;
        }

        return new self(['name' => 'Sail']);
    }

    public function create(array $data = [])
    {
        return false;
    }

    public function update(int $id = null, array $data = [])
    {
        return false;
    }

    public function delete(int $id = null)
    {
        return false;
    }
}
