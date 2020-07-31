<?php
namespace Riki\NpAtobarai\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Widget\Tab;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Exception;
use Magento\Sales\Model\Order;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\Block\Template\Context;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class NpTransaction extends Tab implements TabInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * NpTransaction constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('NP Transaction');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('NP Transaction');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        $order = $this->getOrder();
        if ($order
            && $order->getPayment()
            && $order->getPayment()->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        $order = $this->getOrder();
        if ($order
            && $order->getPayment()
            && $order->getPayment()->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ) {
            return false;
        }

        return true;
    }
}
