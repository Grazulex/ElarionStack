# ElarionStack Stubs

This directory contains stub files that are automatically copied to new projects when using `composer create-project`.

## Available Stubs

### ExampleController.php.stub
Example controller with common patterns:
- Simple JSON response
- Route parameters
- Basic validation

**Copied to:** `app/Controllers/ExampleController.php`

### api.php.stub
Example API routes file with:
- Route groups
- RESTful routing patterns
- Named routes
- Parameter constraints

**Copied to:** `routes/api.php`

## How It Works

When a user runs:
```bash
composer create-project elarion/elarionstack my-api
```

The `post-create-project-cmd` script in `composer.json`:

1. Creates the `app/` directory structure:
   - `app/Controllers/`
   - `app/Models/`
   - `app/Resources/`
   - `app/Middleware/`

2. Copies stub files:
   - `ExampleController.php.stub` → `app/Controllers/ExampleController.php`
   - `api.php.stub` → `routes/api.php`

3. Creates `.env` from `.env.example`

4. Displays next steps to the user

## Adding New Stubs

To add a new stub file:

1. Create the stub file in this directory with `.stub` extension
2. Add a copy command to `composer.json` under `post-create-project-cmd`
3. Update this README

## Example

```json
"post-create-project-cmd": [
    "@php -r \"if (!file_exists('app/Models/Example.php')) { copy('vendor/elarion/elarionstack/stubs/ExampleModel.php.stub', 'app/Models/Example.php'); }\""
]
```
