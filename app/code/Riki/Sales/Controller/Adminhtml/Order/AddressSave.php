<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class AddressSave extends \Magento\Sales\Controller\Adminhtml\Order\AddressSave
{
    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface
     */
    protected $_orderAddressInterface;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_salesAddressHelper;
    /**
     * @var \Riki\Customer\Helper\Region $_rikiRegionHelper
     */
    protected $_rikiRegionHelper;
    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $_emailHelper;

    /**
     * AddressSave constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddressInterface
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Riki\Customer\Helper\Region $rikiRegionHelper
     * @param \Riki\Sales\Helper\Email $emailHelper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Magento\Sales\Api\Data\OrderAddressInterface $orderAddressInterface,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\Customer\Helper\Region $rikiRegionHelper,
        \Riki\Sales\Helper\Email $emailHelper
    ){

        $this->_salesAddressHelper = $addressHelper;
        $this->_orderAddressInterface = $orderAddressInterface;
        $this->_rikiRegionHelper = $rikiRegionHelper;
        $this->_emailHelper = $emailHelper;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    /**
     * Save order address
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        
        $addressId = $this->getRequest()->getParam('address_id');
        /** @var $address \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Sales\Model\Order\Address */
        $address = $this->_orderAddressInterface->load($addressId);
        $data = $this->getRequest()->getPostValue();
        if($data['region_id']){
            $data['region'] = $this->_rikiRegionHelper->getJapanRegion($data['region_id']);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data && $address->getId()) {
            $address->addData($data);
            try {
                if(!$this->_salesAddressHelper->isValidUpdateDataShippingAddress($address)){
                    throw new \Magento\Framework\Exception\LocalizedException(__('This payment method is not allowed'));
                }

                $address->save();
                $this->_eventManager->dispatch(
                    'admin_sales_order_address_update',
                    [
                        'order_id' => $address->getParentId(),
                        'address_id'    =>  $addressId
                    ]
                );
                $this->messageManager->addSuccess(__('You updated the order address.'));
                //send the email change
                $this->_emailHelper->sendMailOrderChange($address->getParentId(),'','AddressSave-Admin controller');
                return $resultRedirect->setPath('sales/*/view', ['order_id' => $address->getParentId()]);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t update the order address right now.'));
            }
            return $resultRedirect->setPath('sales/*/address', ['address_id' => $address->getId()]);
        } else {
            return $resultRedirect->setPath('sales/*/');
        }
    }
}
