<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Rss;

use Magento\Framework\Exception\NotFoundException;

/**
 * Magento Version controller
 */
class Feed extends \Magento\Framework\App\Action\Action
{

    public $rssManager = null;
    public $rssFactory = null;
    public $customerSession = null;
    public $customerAccountManagement = null;
    public $httpAuthentication = null;
    public $logger = null;
    public $auth = null;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Rss\Model\RssManager $rssManager,
        \Magento\Rss\Model\RssFactory $rssFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\HTTP\Authentication $httpAuthentication,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Auth $auth
    ) {
        parent::__construct($context);
        $this->rssManager = $rssManager;
        $this->rssFactory = $rssFactory;
        $this->customerSession = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->httpAuthentication = $httpAuthentication;
        $this->logger = $logger;
        $this->auth = $auth;
    }

    public function execute()
    {


        $type = "ai_rss_feed";
        try {
            $provider = $this->rssManager->getProvider($type);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundException(__($e->getMessage()));
        }

        if ($provider->isAuthRequired() && !$this->auth()) {
            return;
        }

        if (!$provider->isAllowed()) {
            throw new NotFoundException(__('Page not found.'));
        }

        /** @var $rss \Magento\Rss\Model\Rss */
        $rss = $this->rssFactory->create();
        $rss->setDataProvider($provider);

        $this->getResponse()->setHeader('Content-type', 'text/xml; charset=UTF-8');
        $this->getResponse()->setBody($rss->createRssXml());
    }

    protected function auth()
    {
        if (!$this->auth->isLoggedIn()) {
            list($login, $password) = $this->httpAuthentication->getCredentials();

            try {
                $this->auth->login($login, $password);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        if (!$this->auth->isLoggedIn()) {
            $this->httpAuthentication->setAuthenticationFailed('RSS Feeds');
            return false;
        }

        return true;
    }
}
