<?php

namespace Riki\RmaWithoutGoods\Controller\Adminhtml\Rma;

class NewAction extends \Riki\RmaWithoutGoods\Controller\Adminhtml\Rma
{
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * NewAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Shipping\Helper\Carrier $carrierHelper
     * @param \Magento\Rma\Model\Shipping\LabelService $labelService
     * @param \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Shipping\Helper\Carrier $carrierHelper,
        \Magento\Rma\Model\Shipping\LabelService $labelService,
        \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $filesystem,
            $carrierHelper,
            $labelService,
            $rmaDataMapper
        );

        $this->orderHelper = $orderHelper;
    }

    /**
     * Create new RMA
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $customerId = $this->getRequest()->getParam('customer_id');
            $this->_redirect('adminhtml/rma/chooseorder', ['customer_id' => $customerId, 'wg'   =>  1]);
        } else {
            try {
                $this->_initCreateModel();
                $this->_initModel();

                $order = $this->_coreRegistry->registry('current_order');
                if ($this->orderHelper->isDelayPaymentOrder($order)) {
                    /*show notice if this order is delay payment and not allowed to create new return*/
                    if (!$this->orderHelper->isDelayPaymentOrderAllowedReturn($order)) {
                        $this->messageManager->addNotice(__(
                            'This order #%1 is used delay payment, is not allowed to create new return right now.',
                            $order->getIncrementId()
                        ));
                    }
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/rma/index');
                return;
            }

            $this->_initAction();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Return Without Goods'));
            $this->_view->renderLayout();
        }
    }

    /**
     * Check the permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_RmaWithoutGoods::rma_wg_actions_create');
    }
}
