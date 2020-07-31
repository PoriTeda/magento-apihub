<?php
namespace Riki\Sales\Model\CaptureOrder\Consumer;

use Bluecom\Paygent\Model\Paygent;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\MessageQueue\Exception\MessageLocalizedException;
use Riki\MessageQueue\Model\Consumer\FailureExecutorInterface;

class FailureOrderCapture extends OrderCaptureAbstract implements FailureExecutorInterface
{
    const FAILURE_CAPTURE_EXECUTOR_NAME = 'capture_failure';

    /**
     * @param $orderId
     * @return mixed|void
     * @throws LocalizedException
     * @throws MessageLocalizedException
     */
    public function process($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            return;
        } catch (\Exception $e) {
            throw $e;
        }

        if ($order->canInvoice()) {
            try {
                $order->setData(Paygent::SKIP_CALL_CAPTURE_PAYGENT, true);

                $invoice = $this->capture($order);

                $this->captureSuccessfullyCallback($order, $invoice);
            } catch (\Exception $e) {
                throw new MessageLocalizedException(__(
                    'UPDATE ORDER ERROR : %1 : %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
            }
        } else {
            throw new LocalizedException(__('The order #%1: Can not create invoice', $order->getIncrementId()));
        }
    }
}
