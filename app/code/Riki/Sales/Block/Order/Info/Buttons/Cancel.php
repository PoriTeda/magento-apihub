<?php
namespace Riki\Sales\Block\Order\Info\Buttons;
/**
 * Class Cancel
 * @package Riki\Sales\Block\Order\Info\Buttons
 */
class Cancel extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'order/info/buttons/cancel.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;
    /**
     * Cancel constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Http\Context $httpContext,
        \Riki\Sales\Helper\Data $dataHelper,
        \Riki\Loyalty\Model\RewardManagement  $rewardManagement,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->httpContext = $httpContext;
        $this->dataHelper = $dataHelper;
        $this->_rewardManagement = $rewardManagement;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Get url for cancel action
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getCancelUrl($order)
    {
        return $this->getUrl('sales/order/cancel', ['order_id' => $order->getId()]);
    }

    /**
     * Check can show cancel button order
     * 
     * @param $order
     * 
     * @return bool
     */
    public function canShowCancelButton($order)
    {
        $canShow = true;
        $existShipment = $this->dataHelper->checkShipment($order);
        $isCvsStatus = $this->dataHelper->checkCVSMethod($order);
        $status = $this->dataHelper->checkStatusOrderCancel($order);
        if ( $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION ||
            $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI ||
            $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT ||
            !$status ||
            $isCvsStatus ||
            $existShipment) {
            $canShow = false;
        }
        return $canShow;
    }

    /**
     * Check Point
     *
     * @param $order
     * @return bool
     */
    public function checkExpiration($order)
    {
        if ($order->getUsedPoint() && $order->getUsedPoint() > 0) {
            $pointValidate = $this->_rewardManagement->checkPointExpiration($order);
            if (is_array($pointValidate)
                && !$pointValidate['error']
                && $pointValidate['data']->return[0] == \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint::CODE_EXPIRATION
            ) {
                return true;
            }
        }

        return false;
    }
}
