<?php

namespace Nestle\SalesRule\Plugin\Coupon;

class CouponPlugin
{
    public function beforeSetTimesUsed(\Magento\SalesRule\Model\Coupon $subject, $timesUsed)
    {
        if ($subject->getUsageLimit() && $timesUsed > $subject->getUsageLimit()) {
            /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
            $logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Riki\Framework\Helper\Logger\LoggerBuilder::class)
                ->setName('Riki_Ned856')
                ->setFileName('ned856.log')
                ->pushHandlerByAlias(\Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();

            $logger->critical(new \Magento\Framework\Exception\LocalizedException(__(
                'Coupon code %1 - ID %2 times used has been updated exceeding its usage limit %3',
                $subject->getCode(),
                $subject->getCouponId(),
                $subject->getUsageLimit()
            )));
        }
        return null;
    }
}
