## Laravel SQL Entities

This package manages SQL entities (views, materialized views, functions, procedures, triggers) as class-based definitions under `database/entities/`. Entities are decoupled from migrations and always reflect the latest state.

### Key Conventions

- Entity classes live in `database/entities/` (any subdirectory structure).
- Each entity extends `CalebDW\SqlEntities\View`, `CalebDW\SqlEntities\MaterializedView`, `CalebDW\SqlEntities\Function_`, `CalebDW\SqlEntities\Procedure`, or `CalebDW\SqlEntities\Trigger`.
- Entity names default to `snake_case` of the class basename. Override with `#[CalebDW\SqlEntities\Attributes\Name]`, not a `$name` property.
- Prefer attributes in `CalebDW\SqlEntities\Attributes` over properties: `#[Connection]`, `#[DependsOn]`, `#[Columns]`, `#[Returns]`, `#[Table]`, `#[Timing]`, `#[Events]`, and the other attribute classes. Properties are a fallback only.
- Use the `sql-entities-development` skill for detailed implementation patterns.

### Quick Reference

- Create all entities: `php artisan sql-entities:create`
- Drop all entities: `php artisan sql-entities:drop`
- Refresh all entities: `php artisan sql-entities:refresh`
- Enable auto-sync on migration: set `sync => true` in `config/sql-entities.php`.
