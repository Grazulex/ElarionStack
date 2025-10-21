<?php

declare(strict_types=1);

namespace Elarion\Database;

use Elarion\Database\Query\Builder;
use PDO;

/**
 * Base Model Class
 *
 * Active Record pattern implementation for database models.
 * Provides CRUD operations, query building, and attribute management.
 */
abstract class Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected string $table;

    /**
     * Primary key column
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Fillable attributes for mass assignment
     *
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Enable automatic timestamps
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Created at column name
     *
     * @var string
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * Updated at column name
     *
     * @var string
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Model attributes
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Original attributes (for change tracking)
     *
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Indicates if model exists in database
     *
     * @var bool
     */
    protected bool $exists = false;

    /**
     * Database connection
     *
     * @var PDO
     */
    protected static PDO $connection;

    /**
     * Query Builder instance
     *
     * @var Builder|null
     */
    protected static ?Builder $builder = null;

    /**
     * Create new model instance
     *
     * @param array<string, mixed> $attributes Initial attributes
     */
    public function __construct(array $attributes = [])
    {
        if (! isset($this->table)) {
            $this->table = $this->getDefaultTableName();
        }

        $this->fill($attributes);
    }

    /**
     * Get default table name from class name
     *
     * @return string Table name
     */
    protected function getDefaultTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();

        // Convert PascalCase to snake_case and pluralize
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className) ?? '');

        return $snake . 's'; // Simple pluralization
    }

    /**
     * Set database connection
     *
     * @param PDO $connection PDO connection
     */
    public static function setConnection(PDO $connection): void
    {
        self::$connection = $connection;
    }

    /**
     * Get database connection
     *
     * @return PDO Database connection
     */
    protected static function getConnection(): PDO
    {
        if (! isset(self::$connection)) {
            throw new \RuntimeException('Database connection not set. Call Model::setConnection() first.');
        }

        return self::$connection;
    }

    /**
     * Create new query builder instance
     *
     * @return Builder Query builder
     */
    public static function query(): Builder
    {
        // @phpstan-ignore-next-line new.static (Required for Active Record pattern)
        $model = new static();
        $connection = self::getConnection();

        // Determine grammar based on driver
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $grammar = match ($driver) {
            'mysql' => new Query\Grammar\MySqlGrammar(),
            'pgsql' => new Query\Grammar\PostgresGrammar(),
            'sqlite' => new Query\Grammar\SqliteGrammar(),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };

        $builder = new Builder($connection, $grammar);
        $builder->from($model->table);

        return $builder;
    }

    /**
     * Find model by primary key
     *
     * @param int|string $id Primary key value
     * @return static|null Model instance or null
     */
    public static function find(int|string $id): ?static
    {
        // @phpstan-ignore-next-line new.static (Required for Active Record pattern)
        $model = new static();
        $result = static::query()
            ->where($model->primaryKey, '=', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        return static::newFromBuilder($result);
    }

    /**
     * Get all models
     *
     * @return array<static> Array of model instances
     */
    public static function all(): array
    {
        $results = static::query()->get();

        return array_map(fn ($attributes) => static::newFromBuilder($attributes), $results);
    }

    /**
     * Begin where query
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @return Builder Query builder
     */
    public static function where(string $column, mixed $operator = null, mixed $value = null): Builder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * Create new model instance from builder result
     *
     * @param array<string, mixed> $attributes Attributes from database
     * @return static Model instance
     */
    protected static function newFromBuilder(array $attributes): static
    {
        // @phpstan-ignore-next-line new.static (Required for Active Record pattern)
        $model = new static();
        $model->setRawAttributes($attributes);
        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }

    /**
     * Fill model with attributes
     *
     * @param array<string, mixed> $attributes Attributes to fill
     * @return $this Fluent interface
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Check if attribute is fillable
     *
     * @param string $key Attribute name
     * @return bool True if fillable
     */
    protected function isFillable(string $key): bool
    {
        // If fillable is empty, all attributes are fillable
        if (empty($this->fillable)) {
            return true;
        }

        return in_array($key, $this->fillable, true);
    }

    /**
     * Save model to database
     *
     * @return bool Success
     */
    public function save(): bool
    {
        // Add timestamps if enabled
        if ($this->timestamps) {
            $this->updateTimestamps();
        }

        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Perform insert operation
     *
     * @return bool Success
     */
    protected function performInsert(): bool
    {
        $attributes = $this->getAttributesForInsert();

        $id = static::query()->insertGetId($attributes);

        // Set primary key if auto-increment
        $this->setAttribute($this->primaryKey, $id);

        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    /**
     * Perform update operation
     *
     * @return bool Success
     */
    protected function performUpdate(): bool
    {
        // Only update if attributes changed
        if (! $this->isDirty()) {
            return true;
        }

        $attributes = $this->getDirty();
        $primaryKeyValue = $this->getAttribute($this->primaryKey);

        static::query()
            ->where($this->primaryKey, '=', $primaryKeyValue)
            ->update($attributes);

        $this->syncOriginal();

        return true;
    }

    /**
     * Delete model from database
     *
     * @return bool Success
     */
    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $primaryKeyValue = $this->getAttribute($this->primaryKey);

        $affected = static::query()
            ->where($this->primaryKey, '=', $primaryKeyValue)
            ->delete();

        $this->exists = false;

        return $affected > 0;
    }

    /**
     * Update timestamps
     */
    protected function updateTimestamps(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        if (! $this->exists && ! $this->hasAttribute($this->createdAtColumn)) {
            $this->setAttribute($this->createdAtColumn, $timestamp);
        }

        $this->setAttribute($this->updatedAtColumn, $timestamp);
    }

    /**
     * Get attributes for insert
     *
     * @return array<string, mixed> Attributes
     */
    protected function getAttributesForInsert(): array
    {
        $attributes = $this->attributes;

        // Remove primary key if it's null (auto-increment)
        if ($this->getAttribute($this->primaryKey) === null) {
            unset($attributes[$this->primaryKey]);
        }

        return $attributes;
    }

    /**
     * Set raw attributes without fillable check
     *
     * @param array<string, mixed> $attributes Attributes
     */
    protected function setRawAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Set attribute value
     *
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     */
    protected function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get attribute value
     *
     * @param string $key Attribute name
     * @return mixed Attribute value
     */
    protected function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Check if attribute exists
     *
     * @param string $key Attribute name
     * @return bool True if exists
     */
    protected function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Sync original attributes
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    /**
     * Check if model or specific attributes are dirty
     *
     * @param string|array<string>|null $attributes Specific attributes to check
     * @return bool True if dirty
     */
    public function isDirty(string|array|null $attributes = null): bool
    {
        $dirty = $this->getDirty();

        if ($attributes === null) {
            return count($dirty) > 0;
        }

        $attributes = is_array($attributes) ? $attributes : [$attributes];

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get changed attributes
     *
     * @return array<string, mixed> Changed attributes
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get changes since last sync
     *
     * @return array<string, array{0: mixed, 1: mixed}> Changes [old, new]
     */
    public function getChanges(): array
    {
        $changes = [];

        foreach ($this->getDirty() as $key => $value) {
            $changes[$key] = [$this->original[$key] ?? null, $value];
        }

        return $changes;
    }

    /**
     * Convert model to array
     *
     * @return array<string, mixed> Attributes array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert model to JSON
     *
     * @return string JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Magic getter for attributes
     *
     * @param string $key Attribute name
     * @return mixed Attribute value
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes
     *
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset for attributes
     *
     * @param string $key Attribute name
     * @return bool True if set
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }

    /**
     * Magic unset for attributes
     *
     * @param string $key Attribute name
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}
