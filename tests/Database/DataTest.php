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

    public function test_new_record_has_no_identifier(): void
    {
        $record = new PersistentData(['name' => 'Sail']);

        $this->assertNull($record->get_id());
    }

    public function test_remove_is_a_no_op_for_an_unsaved_record(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->remove();

        $this->assertSame([], $record->deleted);
    }

    public function test_remove_deletes_by_identifier(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->set_id(7);
        $record->remove();

        $this->assertSame([7], $record->deleted);
    }

    public function test_save_creates_a_new_record_and_assigns_the_identifier(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->next_id = 42;

        $this->assertSame(42, $record->save());
        $this->assertSame(42, $record->get_id());
        $this->assertSame([['name' => 'Sail']], $record->created);
        $this->assertSame([], $record->updated);
    }

    public function test_save_returns_false_and_keeps_no_identifier_when_create_fails(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->next_id = false;

        $this->assertFalse($record->save());
        $this->assertNull($record->get_id());
    }

    public function test_save_updates_an_existing_record(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->set_id(7);

        $this->assertSame(7, $record->save());
        $this->assertSame([], $record->created);
        $this->assertSame([[7, ['name' => 'Sail']]], $record->updated);
    }

    public function test_save_treats_zero_affected_rows_as_success(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->set_id(7);
        $record->update_result = 0;

        $this->assertSame(7, $record->save());
    }

    public function test_save_returns_false_when_update_fails(): void
    {
        $record = new PersistentData(['name' => 'Sail']);
        $record->set_id(7);
        $record->update_result = false;

        $this->assertFalse($record->save());
    }

    public function test_save_uses_the_given_identifier(): void
    {
        $record = new PersistentData(['name' => 'Sail']);

        $this->assertSame(9, $record->save(9));
        $this->assertSame(9, $record->get_id());
        $this->assertSame([[9, ['name' => 'Sail']]], $record->updated);
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

final class PersistentData extends Data
{
    public int|false $next_id = 1;
    public int|false $update_result = 1;
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    protected array $data = [
        'name' => null,
    ];

    public static function find(int $id)
    {
        return null;
    }

    public function create(array $data = [])
    {
        $this->created[] = $data;

        return $this->next_id;
    }

    public function update(int $id = null, array $data = [])
    {
        $this->updated[] = [$id, $data];

        return $this->update_result;
    }

    public function delete(int $id = null)
    {
        $this->deleted[] = $id;

        return 1;
    }
}
