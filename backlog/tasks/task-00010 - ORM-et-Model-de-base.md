---
id: task-00010
title: ORM et Model de base
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:47'
labels:
  - database
  - orm
  - model
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer un système ORM simple avec une classe Model de base pour mapper les tables de base de données à des objets PHP, inspiré du pattern Active Record.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une classe Model abstraite existe avec méthodes find(), all(), save(), delete()
- [x] #2 Les models peuvent définir leur table, primary key, fillable
- [x] #3 Les models utilisent le Query Builder en interne
- [x] #4 Support des timestamps (created_at, updated_at) automatiques
- [x] #5 Les attributs du model sont accessibles comme propriétés
- [x] #6 Les tests démontrent CRUD complet sur un model
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Analyze existing Query Builder and Database components
2. Design Model architecture (Active Record pattern)
3. Create abstract Model base class with:
   - Configuration properties (table, primaryKey, fillable, timestamps)
   - Query Builder integration
   - Magic property access (__get, __set, __isset, __unset)
4. Implement query methods:
   - find($id): Find by primary key
   - all(): Get all records
   - where(...): Delegate to Query Builder
5. Implement persistence methods:
   - save(): Insert or update based on existence
   - delete(): Delete current record
   - fill($attributes): Mass assignment with fillable guard
6. Implement timestamps support:
   - Auto-set created_at on insert
   - Auto-set updated_at on insert/update
   - Make timestamps optional via $timestamps property
7. Implement attribute management:
   - $attributes array to store data
   - $original array to track changes
   - isDirty(), getChanges() methods
   - toArray(), toJson() methods
8. Create comprehensive tests:
   - Model configuration tests
   - CRUD operations (Create, Read, Update, Delete)
   - Timestamps functionality
   - Magic property access
   - Fillable guard protection
   - Query delegation tests
9. Run quality checks (PHPStan, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
# Implementation Notes

## Summary
Implemented a complete Active Record ORM system with abstract Model base class. The implementation provides CRUD operations, query building integration, automatic timestamps, fillable guard, and comprehensive attribute management.

## Key Features

### Configuration
- Automatic table name from class name (PascalCase → snake_case + pluralization)
- Customizable via `$table`, `$primaryKey`, `$fillable`, `$timestamps` properties
- Flexible timestamp column names (`$createdAtColumn`, `$updatedAtColumn`)

### Query Methods (Static)
- `find($id)`: Find by primary key, returns model instance or null
- `all()`: Get all records as array of model instances
- `where($column, $operator, $value)`: Delegate to Query Builder
- `query()`: Get Query Builder instance for complex queries

### Persistence Methods
- `save()`: Smart insert/update based on model existence
- `delete()`: Delete record from database
- `fill($attributes)`: Mass assignment with fillable guard protection
- Automatic timestamps on insert/update when enabled

### Magic Property Access
- `__get($key)`: Access attributes as properties (`$user->name`)
- `__set($key, $value)`: Set attributes as properties
- `__isset($key)`: Check if attribute exists
- `__unset($key)`: Remove attribute

### Change Tracking
- `isDirty($attributes)`: Check if model/attributes changed
- `getDirty()`: Get changed attributes
- `getChanges()`: Get old/new values for changed attributes
- Automatic original state tracking

### Serialization
- `toArray()`: Convert model to array
- `toJson()`: Convert model to JSON string

## Architecture

**Active Record Pattern**
- Each model instance represents a database row
- Business logic and persistence in same class
- Static methods for queries, instance methods for operations

**Query Builder Integration**
- Uses existing Query Builder for all SQL generation
- Automatic Grammar selection based on PDO driver
- Seamless support for MySQL, PostgreSQL, SQLite

**Smart Save Logic**
- `exists` flag tracks if model is new or existing
- New models → INSERT with `insertGetId()`
- Existing models → UPDATE only dirty attributes
- No-op if no changes detected

## Testing
Created comprehensive test suite (**ModelTest.php**) with:
- 31 tests covering all functionality
- Configuration tests (table, primary key)
- Magic property access tests
- Fillable guard protection tests
- CRUD operation tests (create, read, update, delete)
- Timestamp tests (create, update, disable)
- Change tracking tests (isDirty, getDirty, getChanges)
- Serialization tests (toArray, toJson)
- All tests passing (31/31)

## Quality Assurance
- **PHPStan Level 8**: No errors
- **PHP-CS-Fixer**: All style checks passed
- **Code Coverage**: All Model methods tested

## Usage Example

```php
// Configure model
class User extends Model 
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected bool $timestamps = true;
}

// Set connection (once)
Model::setConnection($pdo);

// Create new record
$user = new User(['name' => 'John', 'email' => 'john@example.com']);
$user->save(); // INSERT

// Find by ID
$user = User::find(1);

// Update
$user->name = 'Jane';
$user->save(); // UPDATE (only changed attributes)

// Query
$users = User::where('status', 'active')->get();

// Delete
$user->delete();

// Check changes
if ($user->isDirty('name')) {
    $changes = $user->getChanges();
}
```

## Files Created
- `src/Database/Model.php` (~530 lines)
- `tests/Database/ModelTest.php` (~480 lines with 31 tests)
<!-- SECTION:NOTES:END -->
