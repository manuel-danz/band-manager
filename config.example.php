<?php
return [
    // Database driver: "mysql" or "sqlite"
    'DB_DRIVER' => 'mysql',

    // MySQL connection settings
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'bandmanager',

    // SQLite database file (used when DB_DRIVER=sqlite)
    'DB_PATH' => __DIR__ . '/database.sqlite',

    // HTTP Basic Auth credentials
    'APP_USER' => 'admin',
    'APP_PASS' => 'secret',
];
