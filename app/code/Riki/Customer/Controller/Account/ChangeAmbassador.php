<?php

namespace Riki\Customer\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Riki\Customer\Model\Address\AddressType;

class ChangeAmbassador extends \Magento\Customer\Controller\Account\Edit
{
    /**
     * @var \Riki\Customer\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * HandleEdit constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param \Riki\Customer\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository,
        DataObjectHelper $dataObjectHelper,
        \Riki\Customer\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->url = $context->getUrl();

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

        $editAccountUrl = $this->helper->getKssEditAccountUrl($this->session->getCustomer(), AddressType::OFFICE);

        if ($editAccountUrl) {
            $editAccountUrl .= $this->url->getUrl('customer/account');
            return $resultRedirect->setUrl($editAccountUrl);
        }

        $this->messageManager->addWarning(__('Please contact to us to update your account information.'));
        return $resultRedirect->setRefererOrBaseUrl();
    }
}
