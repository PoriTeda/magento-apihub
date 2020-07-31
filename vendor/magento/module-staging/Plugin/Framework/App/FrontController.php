<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\Framework\App;

/**
 * Plugin for front controller interface.
 */
class FrontController
{
    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        $this->auth = $auth;
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            if ($this->auth->getUser()) {
                $this->auth->getUser()->reload();

                $isLoggedIn = $this->auth->isLoggedIn();

                $isAllowed = $this->auth->getAuthStorage()->isAllowed(
                    'Magento_Staging'
                );

                if (!$isLoggedIn || !$isAllowed) {
                    $this->forwardRequest($request);
                }
            } else {
                $this->forwardRequest($request);
            }
        }
    }

    /**
     * Forwards request to the 404 page.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     */
    private function forwardRequest(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $request->initForward();
        $request->setActionName('noroute');
    }
}
