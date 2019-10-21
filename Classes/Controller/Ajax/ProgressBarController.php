<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Controller\Ajax;

use Pixelant\PxaPmImporter\Domain\Repository\ProgressRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle backend ajax requests
 *
 * @package Pixelant\PxaPmImporter\Controller
 */
class ProgressBarController
{
    /**
     * @var ProgressRepository
     */
    protected $progressRepository = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->progressRepository = GeneralUtility::makeInstance(ProgressRepository::class);
    }

    /**
     * Check import loading progress status
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function importProgressStatus(ServerRequestInterface $request): ResponseInterface
    {
        $configuration = $request->getParsedBody()['configuration'];

        if ($configuration === 'all') {
            $response = $this->progressRepository->findAll();
        } else {
            $progress = $this->progressRepository->findByConfiguration($configuration);
            $response = $progress !== null ? $progress : ['failed' => true];
        }

        return new JsonResponse($response);
    }
}
