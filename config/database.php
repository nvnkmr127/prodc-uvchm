<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'), // Changed from sqlite to mysql for college system

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (defined('Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007)) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],

            // OPTIMIZED DUMP CONFIGURATION FOR COLLEGE BACKUP SYSTEM
            'dump' => [
                // Use single transaction for InnoDB tables (ensures consistency)
                'useSingleTransaction' => true,

                // Skip table locking (works with single transaction)
                'skipLockTables' => true,

                // Add DROP TABLE statements before CREATE TABLE
                'addDropTable' => true,

                // Don't add DROP DATABASE statement
                'addDropDatabase' => false,

                // Skip comments to reduce file size
                'skipComments' => true,

                // Compress protocol for faster transfer
                'compressProtocol' => true,

                // Exclude tables that can be regenerated or are not critical for college operations
                'excludeTables' => [
                    // Laravel session storage (can be regenerated)
                    'sessions',

                    // Cache tables (can be regenerated)
                    'cache',
                    'cache_locks',

                    // Job queue tables (optional - uncomment if you want to exclude)
                    // 'jobs',
                    // 'failed_jobs',

                    // Activity log tables (if using spatie/laravel-activitylog and logs are very large)
                    // 'activity_log',

                    // Telescope debugging tables (if using Laravel Telescope)
                    // 'telescope_entries',
                    // 'telescope_entries_tags',
                    // 'telescope_monitoring',

                    // Temporary import/export tables
                    'temp_imports',
                    'temp_exports',
                    'temp_student_imports',
                    'temp_fee_imports',

                    // Password reset tokens (these can be regenerated)
                    // 'password_reset_tokens', // Uncomment if you want to exclude

                    // Personal access tokens (can be regenerated if needed)
                    // 'personal_access_tokens', // Uncomment if you want to exclude
                ],

                // Additional mysqldump options for optimal college data backup
                'extraOptions' => [
                    '--single-transaction',      // Ensure consistent backup
                    '--routines',               // Include stored procedures and functions
                    '--triggers',               // Include triggers
                    '--events',                 // Include scheduled events
                    '--quick',                  // Retrieve rows one at a time (memory efficient)
                    '--lock-tables=false',      // Don't lock tables during backup
                    '--no-tablespaces',         // Skip tablespace information
                    '--hex-blob',               // Use hex notation for binary data
                    '--set-gtid-purged=OFF',    // Disable GTID information
                    '--column-statistics=0',    // Disable column statistics (MySQL 8.0+)
                ],

                // Set a reasonable timeout for large college databases
                'timeout' => 60 * 15, // 15 minutes timeout

                // Default character set for dump
                'defaultCharacterSet' => 'utf8mb4',

                'databases' => false,
            ],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (defined('\Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007)) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],

            // MariaDB optimized dump configuration
            'dump' => [
                'useSingleTransaction' => true,
                'skipLockTables' => true,
                'addDropTable' => true,
                'skipComments' => true,
                'excludeTables' => [
                    'sessions',
                    'cache',
                    'cache_locks',
                    'temp_imports',
                    'temp_exports',
                ],
                'extraOptions' => [
                    '--single-transaction',
                    '--routines',
                    '--triggers',
                    '--events',
                    '--quick',
                    '--lock-tables=false',
                    '--no-tablespaces',
                ],
                'timeout' => 60 * 15,
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        // Optional: Read-only connection for reporting/analytics
        'mysql_readonly' => [
            'driver' => 'mysql',
            'url' => env('DB_READONLY_URL'),
            'host' => env('DB_READONLY_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_READONLY_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_READONLY_DATABASE', env('DB_DATABASE', 'laravel')),
            'username' => env('DB_READONLY_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_READONLY_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (defined('\Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007)) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],

            // Simplified dump config for readonly connection
            'dump' => [
                'useSingleTransaction' => true,
                'skipLockTables' => true,
                'addDropTable' => true,
                'extraOptions' => [
                    '--single-transaction',
                    '--quick',
                    '--lock-tables=false',
                ],
                'timeout' => 60 * 10,
            ],
        ],

        // Optional: Backup-specific connection with optimized settings
        'mysql_backup' => [
            'driver' => 'mysql',
            'url' => env('DB_BACKUP_URL', env('DB_URL')),
            'host' => env('DB_BACKUP_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_BACKUP_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_BACKUP_DATABASE', env('DB_DATABASE', 'laravel')),
            'username' => env('DB_BACKUP_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_BACKUP_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (defined('\Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007)) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],

            // Optimized specifically for backup operations
            'dump' => [
                'useSingleTransaction' => true,
                'skipLockTables' => true,
                'addDropTable' => true,
                'skipComments' => true,
                'compressProtocol' => true,

                // Minimal exclusions for complete backup
                'excludeTables' => [
                    'sessions',
                    'cache',
                    'cache_locks',
                ],

                // Maximum optimization for backup speed
                'extraOptions' => [
                    '--single-transaction',
                    '--routines',
                    '--triggers',
                    '--events',
                    '--quick',
                    '--lock-tables=false',
                    '--no-tablespaces',
                    '--hex-blob',
                    '--set-gtid-purged=OFF',
                    '--column-statistics=0',
                    '--opt',                    // Optimize for fast backup
                    '--extended-insert',        // Use multiple-row INSERT syntax
                    '--disable-keys',           // Disable key checks during import
                ],

                'timeout' => 60 * 20, // 20 minutes for large backups
                'defaultCharacterSet' => 'utf8mb4',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
