<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CvsPayment\Block\Adminhtml\Order;

use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;

/**
 * Class View
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class View extends \Magento\Sales\Block\Adminhtml\Order\View
{
    const ORDER_REGENERATE_SLIP = 5;

    /**
     * DatetimeHelper
     *
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * View constructor.
     *
     * @param \Riki\Framework\Helper\Datetime       $datetimeHelper datetimeHelper
     * @param \Magento\Backend\Block\Widget\Context $context        context
     * @param \Magento\Framework\Registry           $registry       registry
     * @param \Magento\Sales\Model\Config           $salesConfig    salesConfig
     * @param \Magento\Sales\Helper\Reorder         $reorderHelper  reorderHelper
     * @param array                                 $data           data
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        array $data = []
    ) {
        $this->datetimeHelper = $datetimeHelper;
        parent::__construct(
            $context,
            $registry,
            $salesConfig,
            $reorderHelper,
            $data
        );
    }

    /**
     * PosConstruct
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        parent::_construct();

        $order = $this->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        if ($order->getStatus() != OrderStatus::STATUS_ORDER_PENDING_CVS) {
            return;
        }

        if ($order->getData('flag_cvs') == 0) {
            return;
        }

        if ($order->getData('csv_start_date')) {
            $today = $this->datetimeHelper->getToday()->getTimestamp();
            $interval =  $today - strtotime($order->getData('csv_start_date'));
            if ($interval < (30*24*60*60)) { // 30 days
                return;
            }
        }

        $this->buttonList->add(
            'order_regenerate_slip',
            [
                'label' => __('Regenerate Slip'),
                'class' => 'regenerate-slip',
                'id' => 'order-view-regenerate-slip-button',
                'onclick' => 'setLocation(\'' . $this->getRegenerateSlipUrl() . '\')'
            ],
            self::ORDER_REGENERATE_SLIP
        );
    }

    /**
     * Get url for regenerate slip
     *
     * @return string
     */
    public function getRegenerateSlipUrl()
    {
        return $this->getUrl('cvspayment/order/regenerateSlip');
    }
}
