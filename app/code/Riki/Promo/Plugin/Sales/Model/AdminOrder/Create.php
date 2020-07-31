<?php
namespace Riki\Promo\Plugin\Sales\Model\AdminOrder;

class Create
{

    /** @var \Riki\Promo\Helper\Data  */
    protected $_helper;

    /**
     * @param \Riki\Promo\Helper\Data $helper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * remove free gift item for reorder action
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null $qty
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    public function beforeInitFromOrderItem(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qty = null
    ) {

        if($this->_helper->isPromoOrderItem($orderItem)) {
            $orderItem->setId(null);
        }

        return [$orderItem, $qty];
    }
}
