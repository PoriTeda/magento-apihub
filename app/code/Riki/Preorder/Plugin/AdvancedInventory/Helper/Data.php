<?php

namespace Riki\Preorder\Plugin\AdvancedInventory\Helper;

use Riki\AdvancedInventory\Model\Assignation;

class Data
{
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;

    /**
     * Data constructor.
     * @param \Riki\Preorder\Helper\Data $helper
     */
    public function __construct(
        \Riki\Preorder\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Riki\AdvancedInventory\Helper\Assignation $subject
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function beforeCanAssignOrder(
        \Riki\AdvancedInventory\Helper\Assignation $subject,
        \Magento\Sales\Model\Order $order
    )
    {
        if ($this->helper->isPreOrder($order)) {
            $order->setData(Assignation::SKIP_ORDER_ASSIGN_FLAG, true);
        }

        return [$order];
    }
}