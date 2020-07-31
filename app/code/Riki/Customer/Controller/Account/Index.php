<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Index extends \Magento\Customer\Controller\AbstractAccount implements HttpGetActionInterface
{
     const XML_PATH_APP_ENABLED = 'mypage_app_config_block/app_config_block/use_my_page_app';
     const XML_PATH_APP_URL = 'mypage_app_config_block/app_config_block/url_my_page_app';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Riki\Customer\Model\SsoConfig $ssoConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_redirect = $context->getRedirect();
        $this->ssoConfig = $ssoConfig;
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->ssoConfig->isEnabledApp()) {
            header('location: ' . $this->ssoConfig->getUrlApp());
           return;
        }
        return $this->resultPageFactory->create();
    }
}
