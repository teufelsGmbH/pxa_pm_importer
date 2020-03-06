<?php

return [
    'pxapmimporter-progress-bar' => [
        'path' => '/pxapmimporter/progress-bar-status',
        'target' => \Pixelant\PxaPmImporter\Controller\Ajax\ProgressBarController::class . '::importProgressDispatcher'
    ],
];
