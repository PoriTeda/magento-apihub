<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Sales\Model\Service;

use Magento\Sales\Model\Service\CreditmemoService as ModelCreditmemoService;
use Riki\Rma\Helper\Refund;

/**
 * Class CreditmemoService
 */
class CreditmemoService extends ModelCreditmemoService
{

    /**
     * Validate For Refund
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo CreditmemoInterface
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateForRefund(\Magento\Sales\Api\Data\CreditmemoInterface $creditmemo)
    {
        if ($creditmemo->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We cannot register an existing credit memo.')
            );
        }

        if (!$creditmemo->getData(Refund::SKIP_VALIDATE_REFUND_AMOUNT_KEY)) {
            return $this->validateRefundAmount($creditmemo);
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateRefundAmount(\Magento\Sales\Api\Data\CreditmemoInterface $creditmemo)
    {
        $getBaseTotalRefunded = $creditmemo->getOrder()->getBaseTotalRefunded();
        $getBaseGrandTotal = $creditmemo->getBaseGrandTotal();
        $getBaseFee = $creditmemo->getBaseFee();

        // Remove base surcharged fee
        $baseOrderRefund = $this->priceCurrency->round(
            $getBaseTotalRefunded + ($getBaseGrandTotal - $getBaseFee)
        );

        $getBaseTotalPaid = $creditmemo->getOrder()->getBaseTotalPaid();

        $baseTotalPaid = $this->priceCurrency->round($getBaseTotalPaid);

        if ($baseOrderRefund > $baseTotalPaid) {
            $baseAvailableRefund = $creditmemo->getOrder()->getBaseTotalPaid()
                - $creditmemo->getOrder()->getBaseTotalRefunded();

            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'The most money available to refund is %1.',
                    $creditmemo->getOrder()->formatBasePrice($baseAvailableRefund)
                )
            );
        }

        return true;
    }
}

