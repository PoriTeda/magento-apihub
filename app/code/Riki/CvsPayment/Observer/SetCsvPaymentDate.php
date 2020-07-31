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
namespace Riki\CvsPayment\Observer;

use Riki\CvsPayment\Model\CvsPayment;

/**
 * Class CheckoutSubmitAllAfter
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class SetCsvPaymentDate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * DatetimeHelper
     *
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * CheckoutSubmitAllAfter constructor.
     *
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper datetimeHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper
    ) {
        $this->datetimeHelper = $datetimeHelper;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * Order
         *
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        if ($payment->getMethod() == CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            $order->setData('csv_start_date', $this->datetimeHelper->toDb());
        }
    }
}
