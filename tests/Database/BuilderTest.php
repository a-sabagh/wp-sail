<?php

namespace WPSail\Tests\Database;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use wpdb;
use WPSail\Database\Builder;

if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (! class_exists('wpdb')) {
    class_alias(MinimalWpdb::class, 'wpdb');
}

/**
 * Stand-in used only when WordPress' real wpdb class is not loaded.
 */
class MinimalWpdb
{
}

final class FakeWpdb extends wpdb
{
    public $prefix = 'wp_';
    public $insert_id = 0;
    public array $queries = [];

    public static function make(): self
    {
        return (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
    }

    public function prepare($query, ...$args)
    {
        $index = 0;

        return preg_replace_callback('/%[dfs]/', function ($match) use (&$index, $args) {
            $value = $args[$index++];

            return match ($match[0]) {
                '%d' => (string) (int) $value,
                '%f' => (string) (float) $value,
                '%s' => "'" . addslashes((string) $value) . "'",
            };
        }, $query);
    }

    public function get_results($query = null, $output = ARRAY_A)
    {
        $this->queries[] = $query;

        return [['id' => 1]];
    }

    public function query($query)
    {
        $this->queries[] = $query;

        return 1;
    }

    public function insert($table, $data, $format = null)
    {
        $this->insert_id = 5;

        return 1;
    }
}

final class BuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['wpdb'] = FakeWpdb::make();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);

        parent::tearDown();
    }

    private function sql(Builder $builder): string
    {
        $builder->get();

        return trim(preg_replace('/\s+/', ' ', $builder->query));
    }

    public function test_where_escapes_string_values(): void
    {
        $sql = $this->sql((new Builder())->table('products')->where('name', '=', "x' OR '1'='1"));

        $this->assertStringContainsString("name = 'x\\' OR \\'1\\'=\\'1'", $sql);
    }

    public function test_where_keeps_integers_unquoted(): void
    {
        $sql = $this->sql((new Builder())->table('products')->where('id', '=', 5)->or_where('slug', 'like', '%x'));

        $this->assertStringContainsString("id = 5 OR slug LIKE '%x'", $sql);
    }

    public function test_where_translates_null_comparisons(): void
    {
        $this->assertStringContainsString(
            'a IS NULL',
            $this->sql((new Builder())->table('p')->where('a', '=', null)),
        );
        $this->assertStringContainsString(
            'a IS NOT NULL',
            $this->sql((new Builder())->table('p')->where('a', '!=', null)),
        );
    }

    public function test_where_rejects_malicious_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Builder())->table('p')->where('id; DROP TABLE x', '=', 1);
    }

    public function test_where_rejects_malicious_operators(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Builder())->table('p')->where('id', '= 1 OR 1=1 --', 1);
    }

    public function test_where_in_escapes_values(): void
    {
        $sql = $this->sql((new Builder())->table('p')->where_in('id', [1, "2') OR ('1"]));

        $this->assertStringContainsString("IN ( 1,'2\\') OR (\\'1' )", $sql);
    }

    public function test_empty_where_in_matches_nothing(): void
    {
        $sql = $this->sql((new Builder())->table('p')->where_in('id', []));

        $this->assertStringContainsString('IN ( NULL )', $sql);
    }

    public function test_where_between_escapes_values(): void
    {
        $sql = $this->sql((new Builder())->table('p')->where_between('price', "1' OR '1", '9'));

        $this->assertStringContainsString("BETWEEN '1\\' OR \\'1' AND '9'", $sql);
    }

    public function test_update_escapes_values_and_validates_columns(): void
    {
        $builder = (new Builder())->table('p')->where('id', '=', 1);
        $builder->update(['name' => "o'brien", 'note' => null, 'qty' => 2]);

        $this->assertStringContainsString("name='o\\'brien',note=NULL,qty=2", $builder->query);

        $this->expectException(InvalidArgumentException::class);
        (new Builder())->table('p')->update(['a=1,b' => 1]);
    }

    public function test_statement_binds_values(): void
    {
        $builder = new Builder();
        $builder->statement('DELETE FROM wp_p WHERE name = %s AND id = %d', ["a' OR 1=1 --", 3]);

        $this->assertSame("DELETE FROM wp_p WHERE name = 'a\\' OR 1=1 --' AND id = 3", $builder->query);
    }
}