<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward sales order creditmemo total model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

class Reward extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * Collect reward totals for credit memo
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $rewardCurrecnyAmountLeft = $order->getRwrdCurrencyAmountInvoiced() - $order->getRwrdCrrncyAmntRefunded();
        $baseRewardCurrecnyAmountLeft = $order->getBaseRwrdCrrncyAmtInvoiced() -
            $order->getBaseRwrdCrrncyAmntRefnded();
        if ($order->getBaseRewardCurrencyAmount() && $baseRewardCurrecnyAmountLeft > 0) {
            if ($baseRewardCurrecnyAmountLeft >= $creditmemo->getBaseGrandTotal()) {
                $rewardCurrecnyAmountLeft = $creditmemo->getGrandTotal();
                $baseRewardCurrecnyAmountLeft = $creditmemo->getBaseGrandTotal();
                $creditmemo->setGrandTotal(0);
                $creditmemo->setBaseGrandTotal(0);
                $creditmemo->setAllowZeroGrandTotal(true);
            } else {
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $rewardCurrecnyAmountLeft);
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseRewardCurrecnyAmountLeft);
            }
            $pointValue = $order->getRewardPointsBalance() / $order->getBaseRewardCurrencyAmount();
            $rewardPointsBalance = $baseRewardCurrecnyAmountLeft * ceil($pointValue);
            $rewardPointsBalanceLeft = $order->getRewardPointsBalance() - $order->getRewardPointsBalanceRefunded();
            if ($rewardPointsBalance > $rewardPointsBalanceLeft) {
                $rewardPointsBalance = $rewardPointsBalanceLeft;
            }
            $creditmemo->setRewardPointsBalance(round($rewardPointsBalance));
            $creditmemo->setRewardCurrencyAmount($rewardCurrecnyAmountLeft);
            $creditmemo->setBaseRewardCurrencyAmount($baseRewardCurrecnyAmountLeft);
        }
        return $this;
    }
}
