## Laravel SQL Entities

This package manages SQL entities (views, materialized views, functions, procedures, triggers) as class-based definitions under `database/entities/`. Entities are decoupled from migrations and always reflect the latest state.

### Key Conventions

- Entity classes live in `database/entities/` (any subdirectory structure).
- Each entity extends `CalebDW\SqlEntities\View`, `CalebDW\SqlEntities\MaterializedView`, `CalebDW\SqlEntities\Function_`, `CalebDW\SqlEntities\Procedure`, or `CalebDW\SqlEntities\Trigger`.
- Entity names default to `snake_case` of the class basename. Override via `protected ?string $name`.
- Use the `sql-entities-development` skill for detailed implementation patterns.

### Quick Reference

- Create all entities: `php artisan sql-entities:create`
- Drop all entities: `php artisan sql-entities:drop`
- Refresh all entities: `php artisan sql-entities:refresh`
- Enable auto-sync on migration: set `sync => true` in `config/sql-entities.php`.
