<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

class Confirmation extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\History
{
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * Confirmation constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Rma\Model\Config $rmaConfig
     * @param \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $collectionFactory
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Rma\Model\Config $rmaConfig,
        \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $collectionFactory,
        \Riki\Sales\Helper\Order $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $rmaConfig, $collectionFactory, $data);
        $this->orderHelper = $orderHelper;
    }

    /**
     * Can create new return
     *      current, this validation just apply for order which is used delay payment
     *
     * @return bool
     */
    public function canCreateNewReturn()
    {
        $order = $this->getOrder();
        if (!$this->orderHelper->isDelayPaymentOrder($order)) {
            return true;
        }

        if ($this->orderHelper->isDelayPaymentOrderAllowedReturn($order)) {
            return true;
        }

        return false;
    }
}
