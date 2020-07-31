<?php

namespace Riki\Subscription\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;

class CheckApplicationLimitPlaceOrder extends CheckApplicationLimitAbstract
{
    /**
     * Plugin FO before place order
     * @param QuoteManagement $subject
     * @param $cartId
     * @param PaymentInterface|null $paymentMethod
     * @return array|mixed
     * @throws LocalizedException
     */
    public function beforePlaceOrder(QuoteManagement $subject, $cartId, PaymentInterface $paymentMethod = null)
    {
        if ($this->customerSession->isLoggedIn()) {
            $applicationLimitValidatingResult = $this->validateApplicationLimit();
            if ($applicationLimitValidatingResult['has_error']) {
                throw new LocalizedException(__($this->subscriptionPageHelper->getApplicationLimitErrorMessage(
                    $applicationLimitValidatingResult
                )));
            }
        }
        return [$cartId, $paymentMethod];
    }
}
