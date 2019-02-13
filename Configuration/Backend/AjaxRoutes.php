<?php

return [
    'pxapmimporter-progress-bar' => [
        'path' => '/pxapmimporter/progress-bar',
        'target' => \Pixelant\PxaPmImporter\Controller\ProgressBarAjaxController::class . '::importProgressStatusAction'
    ],
    'pxapmimporter-all-imports' => [
        'path' => '/pxapmimporter/all-imports',
        'target' => \Pixelant\PxaPmImporter\Controller\ProgressBarAjaxController::class . '::getAllRunningJobs'
    ]
];
