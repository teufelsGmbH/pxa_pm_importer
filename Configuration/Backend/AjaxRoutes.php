<?php

return [
    'pxapmimporter-progress-bar' => [
        'path' => '/pxapmimporter/progress-bar',
        'target' => \Pixelant\PxaPmImporter\Controller\AjaxController::class . '::importProgressStatusAction'
    ]
];
