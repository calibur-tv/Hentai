<?php

return [
    'dsn' => env('SENTRY_DSN'),

    'release' => trim(exec('git log --pretty="%h" -n1 HEAD'))
];
