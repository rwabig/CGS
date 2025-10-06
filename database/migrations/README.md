Place incremental migration files here (e.g., `20250905_add_field.sql`).
Run manually or integrate with a migration tool of your choice.

# Database Migrations

This folder holds incremental schema updates.

### How to apply a migration manually
```bash
mysql -u root -p cgs < database/migrations/20250905_add_certificate_table.sql
for windows
mysql -u root -p cgs < C:\xampp\htdocs\CGS\database\migrations\20230915_create_migrations_log.sql
for postgresql
PS C:\xampp\htdocs\CGS> psql -U postgres -d cgs -f database/migrations/2025_09_28_alter_staff_profiles.sql
```

### Naming convention
Use `YYYYMMDD_description.sql` for clarity.

Example: `20250905_add_certificate_table.sql

With this demo migration, you now have a pattern for safely evolving your schema without touching the base `schema.sql`.
Future updates can just be added as new migration files.
