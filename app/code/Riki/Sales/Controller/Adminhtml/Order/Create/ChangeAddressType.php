<?php

namespace Riki\Sales\Controller\Adminhtml\Order\Create;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Riki\Sales\Model\Config\DeliveryOrderType;

class ChangeAddressType extends \Magento\Sales\Controller\Adminhtml\Order\Create
{

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    protected $_rikiSalesAdminHelper;

    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        RawFactory $resultRawFactory,
        \Riki\Sales\Helper\Admin $adminHelper
    ) {

        $this->resultRawFactory = $resultRawFactory;
        $this->_rikiSalesAdminHelper = $adminHelper;

        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory
        );
    }

    /**
     * Change shipping address type
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $requestType = $this->getRequest()->getParam('type', DeliveryOrderType::SINGLE_ADDRESS);

        if(!in_array($requestType, [DeliveryOrderType::SINGLE_ADDRESS, DeliveryOrderType::MULTIPLE_ADDRESS]))
            $requestType = DeliveryOrderType::SINGLE_ADDRESS;

        try{

            $this->_getOrderCreateModel()->initRuleData();

            $this->_getOrderCreateModel()->resetShippingMethod(true);
            $this->_rikiSalesAdminHelper->changeShippingAddressType($requestType, $this->_getOrderCreateModel());
        }catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order_create/index');
        return $resultRedirect;
    }
}
