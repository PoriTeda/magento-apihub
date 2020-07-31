<?php
namespace Riki\ThirdPartyImportExport\Controller\Order;

use Magento\Framework\Registry as Registry;

class View extends \Riki\ThirdPartyImportExport\Controller\Order
{
    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    private $_customerSession;

    /**
     * View constructor.
     * @param Registry $registry
     * @param \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory
     * @param \Riki\ThirdPartyImportExport\Helper\Order\Config $config
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Registry $registry,
        \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory,
        \Riki\ThirdPartyImportExport\Helper\Order\Config $config,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        parent::__construct($registry, $orderFactory, $config, $context);
        $this->_customerSession = $customerSession;
    }

    public function execute()
    {
        if($this->_customerSession->authenticate()){
            $model = $this->initModel();

            if (!$model->getId()) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('thirdpartyimportexport/order/history');
                return $resultRedirect;
            }
            if($this->_customerSession->getCustomer()->getConsumerDbId() != $model->getCustomerCode()){
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/*');
                return $resultRedirect;
            }
            $resultPage = $this->initResultPage();

            $resultPage->getConfig()
                ->getTitle()
                ->set(__('Order Detail ( Order Number: # %1)', $model->getId()));

            /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
            $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
            if ($navigationBlock) {
                $navigationBlock->setActive('thirdpartyimportexport/order/history');
            }

            return $resultPage;
        }
    }
}
