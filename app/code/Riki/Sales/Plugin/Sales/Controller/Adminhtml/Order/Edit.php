<?php
namespace Riki\Sales\Plugin\Sales\Controller\Adminhtml\Order;

class Edit
{
    protected $_createOrder;

    /**
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     */
    public function __construct(
        \Magento\Sales\Model\AdminOrder\Create $orderCreate
    ) {
        $this->_createOrder = $orderCreate;
    }

    /**
     * @param \Magento\Sales\Controller\Adminhtml\Order\Edit\Index $subject
     * @return $this
     */
    public function beforeExecute(
        \Magento\Sales\Controller\Adminhtml\Order\Edit\Index $subject
    )
    {
        $this->_createOrder->initRuleData();

    }
}