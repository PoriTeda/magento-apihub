<?php

namespace Riki\Customer\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Riki\Subscription\Block\Frontend\Profile\Edit as SubscriptionProfileEditBlock;

class SetUpdateFlag extends \Magento\Customer\Controller\Account\Edit
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SetUpdateFlag constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

        parent::__construct(
            $context,
            $customerSession,
            $resultPageFactory,
            $customerRepository,
            $dataObjectHelper
        );
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $this->session->setHandleEditAccountInformation(true);

        $type = $this->getRequest()->getParam('type');

        switch ($type) {
            case 'homeCompany':
                $urlConfigPath = SubscriptionProfileEditBlock::ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY;
                break;
            case 'home':
                $urlConfigPath = SubscriptionProfileEditBlock::ADDRESS_LINK_EDIT_HOME_NO_COMPANY;
                break;
            case 'company':
                $urlConfigPath = SubscriptionProfileEditBlock::ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY;
                break;
            default:
                $urlConfigPath = null;
        }

        if ($urlConfigPath) {
            return $resultRedirect->setUrl($this->buildUrl($urlConfigPath) . $this->_redirect->getRefererUrl());
        }

        $this->messageManager->addWarning(__('Can not update your address right now.'));

        return $resultRedirect->setRefererOrBaseUrl();
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function buildUrl($path)
    {
        return $this->scopeConfig->getValue($path);
    }
}

