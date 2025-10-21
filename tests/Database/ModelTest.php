<?php

declare(strict_types=1);

namespace Elarion\Tests\Database;

use Elarion\Database\Model;
use Elarion\Database\Query\Builder;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Model Tests
 *
 * Comprehensive tests for Active Record ORM Model.
 */
final class ModelTest extends TestCase
{
    private PDO $pdo;
    private PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);

        // Set SQLite as default driver
        $this->pdo->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn('sqlite');

        Model::setConnection($this->pdo);
    }

    // ========================================
    // CONFIGURATION TESTS
    // ========================================

    public function test_model_uses_default_table_name(): void
    {
        $user = new TestUser();

        // TestUser -> test_users (snake_case + s)
        $this->assertSame('test_users', $user->getTable());
    }

    public function test_model_uses_custom_table_name(): void
    {
        $post = new TestPost();

        $this->assertSame('posts', $post->getTable());
    }

    public function test_model_uses_default_primary_key(): void
    {
        $user = new TestUser();

        $this->assertSame('id', $user->getPrimaryKey());
    }

    public function test_model_uses_custom_primary_key(): void
    {
        $post = new TestPost();

        $this->assertSame('post_id', $post->getPrimaryKey());
    }

    // ========================================
    // MAGIC PROPERTY ACCESS
    // ========================================

    public function test_magic_get_returns_attribute(): void
    {
        $user = new TestUser(['name' => 'John Doe']);

        $this->assertSame('John Doe', $user->name);
    }

    public function test_magic_set_sets_attribute(): void
    {
        $user = new TestUser();
        $user->name = 'Jane Doe';

        $this->assertSame('Jane Doe', $user->name);
    }

    public function test_magic_isset_checks_attribute(): void
    {
        $user = new TestUser(['name' => 'John']);

        $this->assertTrue(isset($user->name));
        $this->assertFalse(isset($user->email));
    }

    public function test_magic_unset_removes_attribute(): void
    {
        $user = new TestUser(['name' => 'John']);

        unset($user->name);

        $this->assertFalse(isset($user->name));
    }

    // ========================================
    // FILLABLE GUARD
    // ========================================

    public function test_fill_only_sets_fillable_attributes(): void
    {
        $user = new TestUser();
        $user->fill([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',  // Not fillable
        ]);

        $this->assertSame('John', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertNull($user->password); // Should not be set
    }

    public function test_constructor_fills_attributes(): void
    {
        $user = new TestUser([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('John', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    // ========================================
    // QUERY BUILDER INTEGRATION
    // ========================================

    public function test_query_returns_builder_instance(): void
    {
        $builder = TestUser::query();

        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function test_where_returns_builder_instance(): void
    {
        $builder = TestUser::where('status', 'active');

        $this->assertInstanceOf(Builder::class, $builder);
    }

    // ========================================
    // FIND METHOD
    // ========================================

    public function test_find_returns_model_when_found(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUser::find(1);

        $this->assertInstanceOf(TestUser::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUser::find(999);

        $this->assertNull($user);
    }

    public function test_found_model_exists_in_database(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John'],
        ]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUser::find(1);

        $this->assertTrue($user->exists());
    }

    // ========================================
    // ALL METHOD
    // ========================================

    public function test_all_returns_array_of_models(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $users = TestUser::all();

        $this->assertCount(2, $users);
        $this->assertContainsOnlyInstancesOf(TestUser::class, $users);
        $this->assertSame('John', $users[0]->name);
        $this->assertSame('Jane', $users[1]->name);
    }

    public function test_all_returns_empty_array_when_no_results(): void
    {
        $this->stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $users = TestUser::all();

        $this->assertSame([], $users);
    }

    // ========================================
    // SAVE METHOD (INSERT)
    // ========================================

    public function test_save_performs_insert_for_new_model(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $user = new TestUserWithoutTimestamps([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $result = $user->save();

        $this->assertTrue($result);
        $this->assertSame('1', $user->id);
        $this->assertTrue($user->exists());
    }

    public function test_save_performs_update_for_existing_model(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ]);
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUserWithoutTimestamps::find(1);
        $user->name = 'Jane';

        $result = $user->save();

        $this->assertTrue($result);
    }

    public function test_save_does_not_update_if_not_dirty(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John'],
        ]);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUserWithoutTimestamps::find(1);

        // No changes made
        $result = $user->save();

        $this->assertTrue($result);
    }

    // ========================================
    // DELETE METHOD
    // ========================================

    public function test_delete_removes_existing_model(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John'],
        ]);
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUserWithoutTimestamps::find(1);
        $result = $user->delete();

        $this->assertTrue($result);
        $this->assertFalse($user->exists());
    }

    public function test_delete_returns_false_for_new_model(): void
    {
        $user = new TestUser();

        $result = $user->delete();

        $this->assertFalse($result);
    }

    // ========================================
    // TIMESTAMPS
    // ========================================

    public function test_save_adds_timestamps_on_insert(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $user = new TestUser(['name' => 'John']);
        $user->save();

        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_save_updates_updated_at_on_update(): void
    {
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'John', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-01 00:00:00'],
        ]);
        $this->stmt->method('rowCount')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $user = TestUser::find(1);
        $originalUpdatedAt = $user->updated_at;

        // Wait a bit to ensure timestamp changes
        sleep(1);

        $user->name = 'Jane';
        $user->save();

        $this->assertNotEquals($originalUpdatedAt, $user->updated_at);
    }

    public function test_timestamps_can_be_disabled(): void
    {
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $user = new TestUserWithoutTimestamps(['name' => 'John']);
        $user->save();

        $this->assertNull($user->created_at);
        $this->assertNull($user->updated_at);
    }

    // ========================================
    // CHANGE TRACKING
    // ========================================

    public function test_is_dirty_returns_true_when_attributes_changed(): void
    {
        $user = new TestUser(['name' => 'John']);
        $user->syncOriginal(); // Simulate loaded from DB

        $user->name = 'Jane';

        $this->assertTrue($user->isDirty());
        $this->assertTrue($user->isDirty('name'));
        $this->assertFalse($user->isDirty('email'));
    }

    public function test_is_dirty_returns_false_when_no_changes(): void
    {
        $user = new TestUser(['name' => 'John']);
        $user->syncOriginal();

        $this->assertFalse($user->isDirty());
    }

    public function test_get_dirty_returns_changed_attributes(): void
    {
        $user = new TestUser(['name' => 'John', 'email' => 'john@example.com']);
        $user->syncOriginal();

        $user->name = 'Jane';

        $dirty = $user->getDirty();

        $this->assertSame(['name' => 'Jane'], $dirty);
    }

    public function test_get_changes_returns_old_and_new_values(): void
    {
        $user = new TestUser(['name' => 'John']);
        $user->syncOriginal();

        $user->name = 'Jane';

        $changes = $user->getChanges();

        $this->assertSame(['name' => ['John', 'Jane']], $changes);
    }

    // ========================================
    // SERIALIZATION
    // ========================================

    public function test_to_array_returns_attributes(): void
    {
        $user = new TestUser([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $array = $user->toArray();

        $this->assertSame([
            'name' => 'John',
            'email' => 'john@example.com',
        ], $array);
    }

    public function test_to_json_returns_json_string(): void
    {
        $user = new TestUser([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $json = $user->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('John', $decoded['name']);
        $this->assertSame('john@example.com', $decoded['email']);
    }
}

// ========================================
// TEST MODELS
// ========================================

/**
 * Test User Model with timestamps
 */
class TestUser extends Model
{
    protected array $fillable = ['name', 'email'];

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function syncOriginal(): void
    {
        parent::syncOriginal();
    }
}

/**
 * Test User Model without timestamps
 */
class TestUserWithoutTimestamps extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected bool $timestamps = false;

    public function exists(): bool
    {
        return $this->exists;
    }
}

/**
 * Test Post Model with custom configuration
 */
class TestPost extends Model
{
    protected string $table = 'posts';
    protected string $primaryKey = 'post_id';
    protected array $fillable = ['title', 'content'];

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }
}
