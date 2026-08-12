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

    // These names remain blocked from ordinary destructive commands. They can
    // be used only by a reviewed, hash-pinned live-migration command after its
    // own backup, maintenance, and exact-target checks have passed.
    'reviewed_live_databases' => [
        'blackgrd',
    ],

    'destructive_commands' => [
        'migrate',
        'migrate:fresh',
        'migrate:reset',
        'migrate:refresh',
        'migrate:rollback',
        'db:wipe',
        'schema:load',
    ],
];
