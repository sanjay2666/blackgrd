<?php

return [
    // Task 5.1 is approved; the environment may still disable its UI during deployment.
    'workflow_definitions' => (bool) env('WORKFLOW_DEFINITIONS_ENABLED', true),
];
