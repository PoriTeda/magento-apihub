<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Staging\Model\VersionManager;

/**
 * Class RestPlugin
 *
 * The main purpose of this plugin is set version from request to instance of VersionManager
 */
class RestPlugin
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * RestPlugin constructor
     * @param VersionManager $versionManager
     * @param Request $request
     */
    public function __construct(VersionManager $versionManager, Request $request)
    {
        $this->versionManager = $versionManager;
        $this->request = $request;
    }

    /**
     * Triggers before original dispatch
     * This method triggers before original \Magento\Webapi\Controller\Rest::dispatch and set version
     * from request params to VersionManager instance
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        $params = $this->request->getRequestData();
        if (empty($params[VersionManager::PARAM_NAME])) {
            return;
        }
        $this->versionManager->setCurrentVersionId($params[VersionManager::PARAM_NAME]);
    }
}
