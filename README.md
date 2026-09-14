# SPECTECHNOLOGY

Bilingual Polish–Irish platform connecting bespoke timber-frame home projects and architects in Ireland with qualified manufacturers in Poland.

## home.pl deployment

1. Create a MySQL/MariaDB database in the home.pl panel.
2. Import `database/schema.sql`.
3. Copy `private/config.example.php` to `private/config.php` on the server and enter the database credentials.
4. Upload the project with PHP 8.1+ enabled.

The registration form stores client, architect and manufacturer profiles in `registrations`. The real database configuration is excluded from Git.
