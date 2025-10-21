<?php

declare(strict_types=1);

namespace Elarion\Tests\Database\Query;

use Elarion\Database\Query\Builder;
use Elarion\Database\Query\Grammar\MySqlGrammar;
use Elarion\Database\Query\Grammar\PostgresGrammar;
use Elarion\Database\Query\Grammar\SqliteGrammar;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Query Builder Tests
 *
 * Comprehensive tests covering all query builder functionality
 * across different SQL grammars (MySQL, PostgreSQL, SQLite).
 */
final class BuilderTest extends TestCase
{
    private PDO $pdo;
    private PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
    }

    /**
     * Create builder with MySQL grammar
     */
    private function mysqlBuilder(): Builder
    {
        return new Builder($this->pdo, new MySqlGrammar());
    }

    /**
     * Create builder with PostgreSQL grammar
     */
    private function postgresBuilder(): Builder
    {
        return new Builder($this->pdo, new PostgresGrammar());
    }

    /**
     * Create builder with SQLite grammar
     */
    private function sqliteBuilder(): Builder
    {
        return new Builder($this->pdo, new SqliteGrammar());
    }

    // ========================================
    // SELECT QUERIES
    // ========================================

    public function test_basic_select(): void
    {
        $builder = $this->mysqlBuilder()->from('users');

        $this->assertSame('select * from `users`', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_select_with_columns(): void
    {
        $builder = $this->mysqlBuilder()
            ->select('id', 'name', 'email')
            ->from('users');

        $this->assertSame('select `id`, `name`, `email` from `users`', $builder->toSql());
    }

    public function test_select_with_array_columns(): void
    {
        $builder = $this->mysqlBuilder()
            ->select(['id', 'name'], 'email')
            ->from('users');

        $this->assertSame('select `id`, `name`, `email` from `users`', $builder->toSql());
    }

    public function test_add_select(): void
    {
        $builder = $this->mysqlBuilder()
            ->select('id', 'name')
            ->addSelect('email')
            ->from('users');

        $this->assertSame('select `id`, `name`, `email` from `users`', $builder->toSql());
    }

    public function test_distinct(): void
    {
        $builder = $this->mysqlBuilder()
            ->distinct()
            ->select('name')
            ->from('users');

        $this->assertSame('select distinct `name` from `users`', $builder->toSql());
    }

    public function test_table_alias(): void
    {
        $builder = $this->mysqlBuilder()
            ->table('users');

        $this->assertSame('select * from `users`', $builder->toSql());
    }

    // ========================================
    // WHERE CLAUSES
    // ========================================

    public function test_where_basic(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('id', '=', 1);

        $this->assertSame('select * from `users` where `id` = ?', $builder->toSql());
        $this->assertSame([1], $builder->getBindings());
    }

    public function test_where_shorthand(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('name', 'John');

        $this->assertSame('select * from `users` where `name` = ?', $builder->toSql());
        $this->assertSame(['John'], $builder->getBindings());
    }

    public function test_multiple_where(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('status', 'active')
            ->where('age', '>', 18);

        $this->assertSame('select * from `users` where `status` = ? and `age` > ?', $builder->toSql());
        $this->assertSame(['active', 18], $builder->getBindings());
    }

    public function test_or_where(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('status', 'active')
            ->orWhere('role', 'admin');

        $this->assertSame('select * from `users` where `status` = ? or `role` = ?', $builder->toSql());
        $this->assertSame(['active', 'admin'], $builder->getBindings());
    }

    public function test_where_in(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->whereIn('id', [1, 2, 3]);

        $this->assertSame('select * from `users` where `id` in (?, ?, ?)', $builder->toSql());
        $this->assertSame([1, 2, 3], $builder->getBindings());
    }

    public function test_where_not_in(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->whereNotIn('status', ['banned', 'deleted']);

        $this->assertSame('select * from `users` where `status` not in (?, ?)', $builder->toSql());
        $this->assertSame(['banned', 'deleted'], $builder->getBindings());
    }

    public function test_where_null(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->whereNull('deleted_at');

        $this->assertSame('select * from `users` where `deleted_at` is null', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_where_not_null(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->whereNotNull('email_verified_at');

        $this->assertSame('select * from `users` where `email_verified_at` is not null', $builder->toSql());
    }

    public function test_where_between(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('products')
            ->whereBetween('price', 10, 100);

        $this->assertSame('select * from `products` where `price` between ? and ?', $builder->toSql());
        $this->assertSame([10, 100], $builder->getBindings());
    }

    public function test_complex_where_combinations(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('status', 'active')
            ->whereIn('role', ['admin', 'moderator'])
            ->whereNotNull('email_verified_at')
            ->whereBetween('age', 18, 65);

        $expected = 'select * from `users` where `status` = ? and `id` in (?, ?) and `email_verified_at` is not null and `age` between ? and ?';
        $this->assertStringContainsString('where `status` = ?', $builder->toSql());
        $this->assertSame(['active', 'admin', 'moderator', 18, 65], $builder->getBindings());
    }

    // ========================================
    // JOIN CLAUSES
    // ========================================

    public function test_inner_join(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->join('posts', 'users.id', '=', 'posts.user_id');

        $this->assertSame('select * from `users` inner join `posts` on `users`.`id` = `posts`.`user_id`', $builder->toSql());
    }

    public function test_left_join(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id');

        $this->assertSame('select * from `users` left join `posts` on `users`.`id` = `posts`.`user_id`', $builder->toSql());
    }

    public function test_right_join(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->rightJoin('posts', 'users.id', '=', 'posts.user_id');

        $this->assertSame('select * from `users` right join `posts` on `users`.`id` = `posts`.`user_id`', $builder->toSql());
    }

    public function test_multiple_joins(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id');

        $sql = $builder->toSql();
        $this->assertStringContainsString('inner join `posts`', $sql);
        $this->assertStringContainsString('left join `comments`', $sql);
    }

    // ========================================
    // ORDER BY, GROUP BY, HAVING
    // ========================================

    public function test_order_by_asc(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->orderBy('name', 'asc');

        $this->assertSame('select * from `users` order by `name` asc', $builder->toSql());
    }

    public function test_order_by_desc(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->orderBy('created_at', 'desc');

        $this->assertSame('select * from `users` order by `created_at` desc', $builder->toSql());
    }

    public function test_multiple_order_by(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'desc');

        $this->assertSame('select * from `users` order by `status` asc, `created_at` desc', $builder->toSql());
    }

    public function test_group_by(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('orders')
            ->groupBy('user_id');

        $this->assertSame('select * from `orders` group by `user_id`', $builder->toSql());
    }

    public function test_multiple_group_by(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('orders')
            ->groupBy('user_id', 'status');

        $this->assertSame('select * from `orders` group by `user_id`, `status`', $builder->toSql());
    }

    public function test_group_by_with_array(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('orders')
            ->groupBy(['user_id', 'status']);

        $this->assertSame('select * from `orders` group by `user_id`, `status`', $builder->toSql());
    }

    public function test_having(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('orders')
            ->groupBy('user_id')
            ->having('total', '>', 1000);

        $this->assertSame('select * from `orders` group by `user_id` having `total` > ?', $builder->toSql());
        $this->assertSame([1000], $builder->getBindings());
    }

    // ========================================
    // LIMIT AND OFFSET
    // ========================================

    public function test_limit(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->limit(10);

        $this->assertSame('select * from `users` limit 10', $builder->toSql());
    }

    public function test_take_alias(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->take(5);

        $this->assertSame('select * from `users` limit 5', $builder->toSql());
    }

    public function test_offset(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->offset(20);

        $this->assertSame('select * from `users` offset 20', $builder->toSql());
    }

    public function test_skip_alias(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->skip(15);

        $this->assertSame('select * from `users` offset 15', $builder->toSql());
    }

    public function test_limit_with_offset(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->limit(10)
            ->offset(20);

        $this->assertSame('select * from `users` limit 10 offset 20', $builder->toSql());
    }

    // ========================================
    // AGGREGATE FUNCTIONS
    // ========================================

    public function test_count_aggregate(): void
    {
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 42]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $count = $builder->count();

        $this->assertSame(42, $count);
    }

    public function test_max_aggregate(): void
    {
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 150.50]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('products');
        $max = $builder->max('price');

        $this->assertSame(150.50, $max);
    }

    public function test_min_aggregate(): void
    {
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 10.00]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('products');
        $min = $builder->min('price');

        $this->assertSame(10.00, $min);
    }

    public function test_avg_aggregate(): void
    {
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 75.25]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('products');
        $avg = $builder->avg('price');

        $this->assertSame(75.25, $avg);
    }

    public function test_sum_aggregate(): void
    {
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 5000]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('orders');
        $sum = $builder->sum('total');

        $this->assertSame(5000, $sum);
    }

    public function test_count_distinct(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->distinct();

        // Mock to verify SQL generation for distinct count
        $this->stmt->method('fetchAll')->willReturn([['aggregate' => 10]]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $count = $builder->count();
        $this->assertSame(10, $count);
    }

    // ========================================
    // INSERT OPERATIONS
    // ========================================

    public function test_insert(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $result = $builder->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $this->assertTrue($result);
    }

    public function test_insert_get_id(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->pdo->method('lastInsertId')->willReturn('123');

        $builder = $this->mysqlBuilder()->from('users');
        $id = $builder->insertGetId([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertSame('123', $id);
    }

    // ========================================
    // UPDATE OPERATIONS
    // ========================================

    public function test_update(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('id', 1);

        $affected = $builder->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertSame(1, $affected);
    }

    public function test_increment(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('id', 1);

        $affected = $builder->increment('login_count', 1);

        $this->assertSame(1, $affected);
    }

    public function test_increment_by_amount(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('products')
            ->where('id', 5);

        $affected = $builder->increment('views', 10);

        $this->assertSame(1, $affected);
    }

    public function test_decrement(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('products')
            ->where('id', 5);

        $affected = $builder->decrement('stock', 1);

        $this->assertSame(1, $affected);
    }

    // ========================================
    // DELETE OPERATIONS
    // ========================================

    public function test_delete(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('status', 'banned');

        $affected = $builder->delete();

        $this->assertSame(1, $affected);
    }

    public function test_delete_with_multiple_conditions(): void
    {
        $this->stmt->method('rowCount')->willReturn(5);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('status', 'inactive')
            ->where('last_login', '<', '2020-01-01');

        $affected = $builder->delete();

        $this->assertSame(5, $affected);
    }

    // ========================================
    // RESULT FETCHING
    // ========================================

    public function test_get(): void
    {
        $expectedResults = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $this->stmt->method('fetchAll')->willReturn($expectedResults);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $results = $builder->get();

        $this->assertSame($expectedResults, $results);
    }

    public function test_first(): void
    {
        $expectedResults = [['id' => 1, 'name' => 'John']];

        $this->stmt->method('fetchAll')->willReturn($expectedResults);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $result = $builder->first();

        $this->assertSame(['id' => 1, 'name' => 'John'], $result);
    }

    public function test_first_returns_null_when_no_results(): void
    {
        $this->stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $result = $builder->first();

        $this->assertNull($result);
    }

    public function test_find(): void
    {
        $expectedResults = [['id' => 42, 'name' => 'John']];

        $this->stmt->method('fetchAll')->willReturn($expectedResults);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $result = $builder->find(42);

        $this->assertSame(['id' => 42, 'name' => 'John'], $result);
    }

    public function test_find_with_custom_column(): void
    {
        $expectedResults = [['uuid' => 'abc-123', 'name' => 'John']];

        $this->stmt->method('fetchAll')->willReturn($expectedResults);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $result = $builder->find('abc-123', 'uuid');

        $this->assertSame(['uuid' => 'abc-123', 'name' => 'John'], $result);
    }

    public function test_pluck(): void
    {
        $expectedResults = [
            ['email' => 'john@example.com'],
            ['email' => 'jane@example.com'],
            ['email' => 'bob@example.com'],
        ];

        $this->stmt->method('fetchAll')->willReturn($expectedResults);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $builder = $this->mysqlBuilder()->from('users');
        $emails = $builder->pluck('email');

        $this->assertSame(['john@example.com', 'jane@example.com', 'bob@example.com'], $emails);
    }

    // ========================================
    // GRAMMAR-SPECIFIC TESTS
    // ========================================

    public function test_mysql_identifier_wrapping(): void
    {
        $builder = $this->mysqlBuilder()
            ->select('users.id', 'users.name')
            ->from('users')
            ->where('users.status', 'active');

        $sql = $builder->toSql();
        $this->assertStringContainsString('`users`.`id`', $sql);
        $this->assertStringContainsString('`users`.`name`', $sql);
        $this->assertStringContainsString('`users`.`status`', $sql);
    }

    public function test_postgres_identifier_wrapping(): void
    {
        $builder = $this->postgresBuilder()
            ->select('users.id', 'users.name')
            ->from('users')
            ->where('users.status', 'active');

        $sql = $builder->toSql();
        $this->assertStringContainsString('"users"."id"', $sql);
        $this->assertStringContainsString('"users"."name"', $sql);
        $this->assertStringContainsString('"users"."status"', $sql);
    }

    public function test_sqlite_identifier_wrapping(): void
    {
        $builder = $this->sqliteBuilder()
            ->select('users.id', 'users.name')
            ->from('users')
            ->where('users.status', 'active');

        $sql = $builder->toSql();
        $this->assertStringContainsString('"users"."id"', $sql);
        $this->assertStringContainsString('"users"."name"', $sql);
        $this->assertStringContainsString('"users"."status"', $sql);
    }

    public function test_asterisk_not_wrapped(): void
    {
        $builder = $this->mysqlBuilder()->from('users');

        $sql = $builder->toSql();
        $this->assertStringContainsString('select *', $sql);
        $this->assertStringNotContainsString('`*`', $sql);
    }

    public function test_table_column_asterisk_not_wrapped(): void
    {
        $builder = $this->mysqlBuilder()
            ->select('users.*')
            ->from('users');

        $sql = $builder->toSql();
        $this->assertStringContainsString('`users`.*', $sql);
    }

    // ========================================
    // COMPLEX QUERY TESTS
    // ========================================

    public function test_complex_query_with_all_clauses(): void
    {
        $builder = $this->mysqlBuilder()
            ->select('users.id', 'users.name', 'COUNT(posts.id) as post_count')
            ->from('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->where('users.status', 'active')
            ->whereNotNull('users.email_verified_at')
            ->groupBy('users.id', 'users.name')
            ->having('post_count', '>', 5)
            ->orderBy('post_count', 'desc')
            ->limit(10)
            ->offset(0);

        $sql = $builder->toSql();

        $this->assertStringContainsString('select', $sql);
        $this->assertStringContainsString('from `users`', $sql);
        $this->assertStringContainsString('left join', $sql);
        $this->assertStringContainsString('where', $sql);
        $this->assertStringContainsString('group by', $sql);
        $this->assertStringContainsString('having', $sql);
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('limit 10', $sql);
        $this->assertStringContainsString('offset 0', $sql);
    }

    public function test_parameter_binding_order(): void
    {
        $builder = $this->mysqlBuilder()
            ->from('users')
            ->where('name', 'John')
            ->whereIn('status', ['active', 'pending'])
            ->whereBetween('age', 18, 65);

        $bindings = $builder->getBindings();

        $this->assertSame(['John', 'active', 'pending', 18, 65], $bindings);
    }

    public function test_fluent_interface_returns_self(): void
    {
        $builder = $this->mysqlBuilder();

        $this->assertSame($builder, $builder->from('users'));
        $this->assertSame($builder, $builder->select('id', 'name'));
        $this->assertSame($builder, $builder->where('status', 'active'));
        $this->assertSame($builder, $builder->orderBy('created_at'));
        $this->assertSame($builder, $builder->limit(10));
    }

    public function test_query_builder_is_reusable(): void
    {
        $builder = $this->mysqlBuilder()->from('users');

        // First query
        $sql1 = $builder->where('status', 'active')->toSql();
        $this->assertStringContainsString('where `status` = ?', $sql1);

        // Builder maintains state
        $sql2 = $builder->where('age', '>', 18)->toSql();
        $this->assertStringContainsString('where `status` = ? and `age` > ?', $sql2);
        $this->assertSame(['active', 18], $builder->getBindings());
    }
}
