<?php
/**
 * Shipping Provider
 * 
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShippingProvider\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;

/**
 * Class DetailShippingFeeBeforeOrderSave
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class DetailShippingFeeBeforeOrderSave implements ObserverInterface
{
    /**
     * QuoteRepository
     *
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * LoggerInterface
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    /**
     * DetailShippingFeeBeforeOrderSave constructor.
     *
     * @param QuoteRepository          $quoteRepository Quote Repository
     * @param \Psr\Log\LoggerInterface $logger         LoggerInterface
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->loggerInterface = $logger;
    }

    /**
     * Save shipping_fee_by_address into sales_order table
     *
     * @param Observer $observer Observer
     *
     * @return $this
     *
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /**
         * Order
         *
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getEvent()->getOrder();
        try {
            if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
                return $this;
            }

            $sharedStoreIds[] = $order->getStoreId();
            $cart = $this->quoteRepository->get($order->getQuoteId(), $sharedStoreIds);
            if ($cart) {
                $shippingFeeByAddress = $cart->getData('shipping_fee_by_address');
                if ($shippingFeeByAddress) {

                    $currentShippingInfoValue = $order->getData('shipping_fee_by_address');

                    if ($currentShippingInfoValue && $shippingFeeByAddress != $currentShippingInfoValue) {
                        try {
                            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-1620.log');
                            $logger = new \Zend\Log\Logger();
                            $logger->addWriter($writer);
                            $message = 'The order #' . $order->getIncrementId() . ' has been changed shipping fee by address.';
                            $message .= PHP_EOL. 'The cart value: ' . $shippingFeeByAddress;
                            $message .= PHP_EOL. 'The order value: ' . $currentShippingInfoValue;
                            throw new LocalizedException(__($message));
                        } catch (LocalizedException $e) {
                            $logger->info($e);
                        }
                    }
                    $order->setData('shipping_fee_by_address', $shippingFeeByAddress);
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->loggerInterface->info($e->getMessage());
        }

        return $this;
    }
}
