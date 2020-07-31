<?php
namespace Riki\AdvancedInventory\Block\Adminhtml\Sales\Order\View\Tab;

class OutOfStocks extends \Magento\Backend\Block\Widget\Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * OutOfStocks constructor.
     *
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Out Of Stocks');
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Out Of Stocks');
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function canShowTab()
    {
        $parentBlock = $this->getParentBlock();
        if (!$parentBlock instanceof \Magento\Sales\Block\Adminhtml\Order\View\Tabs) {
            return false;
        }

        $order = $parentBlock->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return false;
        }

        $allowedTypes = [
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_ORDER_HANPUKAI,
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_ORDER_SUBSCRIPTION,
            \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
        ];
        if (!in_array($order->getData('riki_type'), $allowedTypes)) {
            return false;
        }

        /** @var \Riki\AdvancedInventory\Block\Adminhtml\OutOfStock\Grid $grid */
        $grid = $this->getChildBlock('sales_order_view_tab_out_of_stock_view');
        if ($grid) {
            $grid->setOriginalOrder($order);
            $grid->setGeneratedOrder($this->orderFactory->create());
            $grid->setReturnUrl($this->getUrl('sales/order/view', ['order_id' => $order->getId()]));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

}