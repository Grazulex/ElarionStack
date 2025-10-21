<?php

declare(strict_types=1);

namespace Elarion\Tests\Support;

use Elarion\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Collection Tests
 *
 * Comprehensive tests for Collection class.
 */
final class CollectionTest extends TestCase
{
    // ========================================
    // CONSTRUCTION
    // ========================================

    public function test_can_create_empty_collection(): void
    {
        $collection = new Collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function test_can_create_collection_with_array(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertCount(3, $collection);
    }

    public function test_can_create_collection_with_make(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(3, $collection);
    }

    // ========================================
    // MAP
    // ========================================

    public function test_map_transforms_items(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->map(fn ($item) => $item * 2);

        $this->assertSame([2, 4, 6], $result->toArray());
    }

    public function test_map_receives_key_and_value(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $result = $collection->map(fn ($value, $key) => $key . $value);

        $this->assertSame(['a' => 'a1', 'b' => 'b2'], $result->toArray());
    }

    public function test_map_preserves_keys(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $result = $collection->map(fn ($item) => $item * 2);

        $this->assertSame(['a' => 2, 'b' => 4], $result->toArray());
    }

    // ========================================
    // FILTER
    // ========================================

    public function test_filter_removes_falsy_values_by_default(): void
    {
        $collection = Collection::make([1, 0, 2, false, 3, null, 4]);

        $result = $collection->filter();

        $this->assertSame([0 => 1, 2 => 2, 4 => 3, 6 => 4], $result->toArray());
    }

    public function test_filter_with_callback(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->filter(fn ($item) => $item > 2);

        $this->assertSame([2 => 3, 3 => 4, 4 => 5], $result->toArray());
    }

    public function test_filter_receives_key_and_value(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

        $result = $collection->filter(fn ($value, $key) => $key !== 'b');

        $this->assertSame(['a' => 1, 'c' => 3], $result->toArray());
    }

    // ========================================
    // REDUCE
    // ========================================

    public function test_reduce_to_single_value(): void
    {
        $collection = Collection::make([1, 2, 3, 4]);

        $result = $collection->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(10, $result);
    }

    public function test_reduce_with_array_result(): void
    {
        $collection = Collection::make(['a', 'b', 'c']);

        $result = $collection->reduce(fn ($carry, $item) => array_merge($carry, [$item => strtoupper($item)]), []);

        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $result);
    }

    // ========================================
    // EACH
    // ========================================

    public function test_each_iterates_over_items(): void
    {
        $collection = Collection::make([1, 2, 3]);
        $result = [];

        $collection->each(function ($item) use (&$result) {
            $result[] = $item * 2;
        });

        $this->assertSame([2, 4, 6], $result);
    }

    public function test_each_can_break_early(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);
        $result = [];

        $collection->each(function ($item) use (&$result) {
            if ($item > 3) {
                return false;
            }
            $result[] = $item;
        });

        $this->assertSame([1, 2, 3], $result);
    }

    public function test_each_returns_collection_for_chaining(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->each(fn ($item) => $item)->map(fn ($item) => $item * 2);

        $this->assertSame([2, 4, 6], $result->toArray());
    }

    // ========================================
    // FIRST
    // ========================================

    public function test_first_returns_first_item(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame(1, $collection->first());
    }

    public function test_first_with_callback(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->first(fn ($item) => $item > 3);

        $this->assertSame(4, $result);
    }

    public function test_first_returns_default_when_empty(): void
    {
        $collection = Collection::make([]);

        $this->assertSame('default', $collection->first(null, 'default'));
    }

    public function test_first_returns_default_when_no_match(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->first(fn ($item) => $item > 10, 'default');

        $this->assertSame('default', $result);
    }

    // ========================================
    // LAST
    // ========================================

    public function test_last_returns_last_item(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame(3, $collection->last());
    }

    public function test_last_with_callback(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->last(fn ($item) => $item < 4);

        $this->assertSame(3, $result);
    }

    public function test_last_returns_default_when_empty(): void
    {
        $collection = Collection::make([]);

        $this->assertSame('default', $collection->last(null, 'default'));
    }

    // ========================================
    // GET / NTH
    // ========================================

    public function test_get_returns_item_by_key(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $collection->get('a'));
        $this->assertSame(2, $collection->get('b'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $collection = Collection::make(['a' => 1]);

        $this->assertSame('default', $collection->get('b', 'default'));
    }

    public function test_nth_returns_item_by_index(): void
    {
        $collection = Collection::make(['a' => 10, 'b' => 20, 'c' => 30]);

        $this->assertSame(10, $collection->nth(0));
        $this->assertSame(20, $collection->nth(1));
        $this->assertSame(30, $collection->nth(2));
    }

    public function test_nth_returns_default_when_out_of_bounds(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame('default', $collection->nth(10, 'default'));
    }

    // ========================================
    // SORT
    // ========================================

    public function test_sort_without_callback(): void
    {
        $collection = Collection::make([3, 1, 2]);

        $result = $collection->sort();

        $this->assertSame([1 => 1, 2 => 2, 0 => 3], $result->toArray());
    }

    public function test_sort_with_callback(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->sort(fn ($a, $b) => $b <=> $a);

        $this->assertSame([2 => 3, 1 => 2, 0 => 1], $result->toArray());
    }

    // ========================================
    // SORTBY
    // ========================================

    public function test_sort_by_key(): void
    {
        $collection = Collection::make([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35],
        ]);

        $result = $collection->sortBy('age');

        $this->assertSame([
            1 => ['name' => 'Jane', 'age' => 25],
            0 => ['name' => 'John', 'age' => 30],
            2 => ['name' => 'Bob', 'age' => 35],
        ], $result->toArray());
    }

    public function test_sort_by_callback(): void
    {
        $collection = Collection::make([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        $result = $collection->sortBy(fn ($item) => $item['age']);

        $this->assertSame(25, $result->first()['age']);
        $this->assertSame(30, $result->last()['age']);
    }

    public function test_sort_by_descending(): void
    {
        $collection = Collection::make([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35],
        ]);

        $result = $collection->sortBy('age', descending: true);

        $this->assertSame(35, $result->first()['age']);
    }

    // ========================================
    // GROUPBY
    // ========================================

    public function test_group_by_key(): void
    {
        $collection = Collection::make([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'vegetable', 'name' => 'carrot'],
        ]);

        $result = $collection->groupBy('type');

        $this->assertCount(2, $result);
        $this->assertCount(2, $result->get('fruit'));
        $this->assertCount(1, $result->get('vegetable'));
    }

    public function test_group_by_callback(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5, 6]);

        $result = $collection->groupBy(fn ($item) => $item % 2 === 0 ? 'even' : 'odd');

        $this->assertCount(2, $result);
        $this->assertCount(3, $result->get('odd'));
        $this->assertCount(3, $result->get('even'));
    }

    // ========================================
    // PLUCK
    // ========================================

    public function test_pluck_extracts_values(): void
    {
        $collection = Collection::make([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        $result = $collection->pluck('name');

        $this->assertSame(['John', 'Jane'], $result->toArray());
    }

    public function test_pluck_with_key(): void
    {
        $collection = Collection::make([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);

        $result = $collection->pluck('name', 'id');

        $this->assertSame([1 => 'John', 2 => 'Jane'], $result->toArray());
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    public function test_count(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame(3, $collection->count());
    }

    public function test_is_empty(): void
    {
        $this->assertTrue(Collection::make([])->isEmpty());
        $this->assertFalse(Collection::make([1])->isEmpty());
    }

    public function test_is_not_empty(): void
    {
        $this->assertFalse(Collection::make([])->isNotEmpty());
        $this->assertTrue(Collection::make([1])->isNotEmpty());
    }

    public function test_to_array(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function test_to_json(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $this->assertSame('{"a":1,"b":2}', $collection->toJson());
    }

    public function test_values(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

        $result = $collection->values();

        $this->assertSame([1, 2, 3], $result->toArray());
    }

    public function test_keys(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

        $result = $collection->keys();

        $this->assertSame(['a', 'b', 'c'], $result->toArray());
    }

    public function test_take_positive(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->take(3);

        $this->assertSame([1, 2, 3], $result->toArray());
    }

    public function test_take_negative(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->take(-2);

        $this->assertSame([3 => 4, 4 => 5], $result->toArray());
    }

    public function test_slice(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection->slice(2, 2);

        $this->assertSame([2 => 3, 3 => 4], $result->toArray());
    }

    public function test_implode(): void
    {
        $collection = Collection::make(['a', 'b', 'c']);

        $this->assertSame('a,b,c', $collection->implode(','));
    }

    public function test_has(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $this->assertTrue($collection->has('a'));
        $this->assertFalse($collection->has('c'));
    }

    public function test_sum(): void
    {
        $collection = Collection::make([1, 2, 3, 4]);

        $this->assertSame(10, $collection->sum());
    }

    public function test_sum_with_key(): void
    {
        $collection = Collection::make([
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ]);

        $this->assertSame(60, $collection->sum('price'));
    }

    public function test_avg(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $this->assertSame(3.0, $collection->avg());
    }

    public function test_avg_with_key(): void
    {
        $collection = Collection::make([
            ['score' => 80],
            ['score' => 90],
            ['score' => 100],
        ]);

        $this->assertSame(90.0, $collection->avg('score'));
    }

    public function test_avg_returns_null_for_empty_collection(): void
    {
        $collection = Collection::make([]);

        $this->assertNull($collection->avg());
    }

    public function test_merge(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->merge([4, 5]);

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    // ========================================
    // INTERFACES
    // ========================================

    public function test_iterator(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = [];
        foreach ($collection as $item) {
            $result[] = $item;
        }

        $this->assertSame([1, 2, 3], $result);
    }

    public function test_array_access_offset_exists(): void
    {
        $collection = Collection::make(['a' => 1]);

        $this->assertTrue(isset($collection['a']));
        $this->assertFalse(isset($collection['b']));
    }

    public function test_array_access_offset_get(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $collection['a']);
        $this->assertSame(2, $collection['b']);
    }

    public function test_array_access_offset_set(): void
    {
        $collection = Collection::make([]);

        $collection['a'] = 1;

        $this->assertSame(1, $collection['a']);
    }

    public function test_array_access_offset_set_null(): void
    {
        $collection = Collection::make([]);

        $collection[] = 1;
        $collection[] = 2;

        $this->assertSame([1, 2], $collection->toArray());
    }

    public function test_array_access_offset_unset(): void
    {
        $collection = Collection::make(['a' => 1, 'b' => 2]);

        unset($collection['a']);

        $this->assertFalse(isset($collection['a']));
        $this->assertTrue(isset($collection['b']));
    }

    public function test_json_serialize(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame('[1,2,3]', json_encode($collection));
    }

    // ========================================
    // METHOD CHAINING
    // ========================================

    public function test_method_chaining(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection
            ->filter(fn ($item) => $item > 2)
            ->map(fn ($item) => $item * 2)
            ->values();

        $this->assertSame([6, 8, 10], $result->toArray());
    }

    public function test_complex_chaining(): void
    {
        $collection = Collection::make([
            ['name' => 'John', 'age' => 30, 'score' => 85],
            ['name' => 'Jane', 'age' => 25, 'score' => 90],
            ['name' => 'Bob', 'age' => 35, 'score' => 75],
            ['name' => 'Alice', 'age' => 28, 'score' => 95],
        ]);

        $result = $collection
            ->filter(fn ($item) => $item['score'] >= 85)
            ->sortBy('age')
            ->pluck('name');

        $this->assertSame(['Jane', 'Alice', 'John'], $result->values()->toArray());
    }
}
