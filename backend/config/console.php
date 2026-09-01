<?php

use app\command\ProcessJobs;

return [
    'commands' => [
        'jobs:process' => ProcessJobs::class,
    ],
];
