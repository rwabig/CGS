Place incremental migration files here (e.g., `20250905_add_field.sql`).
Run manually or integrate with a migration tool of your choice.

# Database Migrations

This folder holds incremental schema updates.

### How to apply a migration manually
```bash
mysql -u root -p cgs < database/migrations/20250905_add_certificate_table.sql
```

### Naming convention
Use `YYYYMMDD_description.sql` for clarity.

Example: `20250905_add_certificate_table.sql

With this demo migration, you now have a pattern for safely evolving your schema without touching the base `schema.sql`.
Future updates can just be added as new migration files.
