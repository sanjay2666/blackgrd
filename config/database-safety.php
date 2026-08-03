<?php

return [
    'allowed_suffixes' => [
        '_test',
        '_testing',
        '_tmp',
        '_temp',
        '_recovery',
        '_disposable',
    ],

    'blocked_names' => [
        'blackgrd',
        'blackgrd_erp',
        'production',
        'live',
    ],

    'destructive_commands' => [
        'migrate:fresh',
        'migrate:reset',
        'migrate:refresh',
        'migrate:rollback',
        'db:wipe',
        'schema:load',
    ],
];
