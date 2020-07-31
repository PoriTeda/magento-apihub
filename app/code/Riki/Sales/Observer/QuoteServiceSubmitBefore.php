<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class QuoteServiceSubmitBefore implements ObserverInterface
{
    protected $_taxHelper;

    /**
     * @param \Riki\Tax\Helper\Data $taxHelper
     */
    public function __construct(
        \Riki\Tax\Helper\Data $taxHelper
    ){
        $this->_taxHelper = $taxHelper;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         * @var \Magento\Quote\Model\Quote $quote
         */
        $order = $observer->getOrder();
        $quote = $observer->getQuote();

        $orderChannel = $quote->getOrderChannel()?$quote->getOrderChannel():'online';
        $order->setOrderChannel($orderChannel);
        $order->setChargeType($quote->getChargeType());
        $order->setOriginalOrderId($quote->getOriginalOrderId());
        $order->setReplacementReason($quote->getReplacementReason());
        $order->setFreeSamplesWbs($quote->getFreeSamplesWbs());
        $order->setSiebelEnquiryId($quote->getSiebelEnquiryId());
        $order->setSubstitution($quote->getSubstitution());
        $order->setFreeOfCharge($quote->getFreeOfCharge());
        $order->setCampaignId($quote->getCampaignId());
        $order->setAllowedEarnedPoint($quote->getAllowedEarnedPoint());

        $order->setIsMultipleShipping($quote->getIsMultipleShipping());
        $order->setFreeDeliveryWbs($quote->getFreeDeliveryWbs());
        $order->setFreeShippingFeeWbs($quote->getFreeShippingFeeWbs());

        $order->setCustomerConsumerDbId($quote->getCustomerConsumerDbId());
        $order->setCustomerCompanyName($quote->getCustomerCompanyName());
        $order->setCustomerOfflineCustomer($quote->getCustomerOfflineCustomer());
        $order->setCustomerKeyWorkPhNum($quote->getCustomerKeyWorkPhNum());
        $order->setCustomerAmbType($quote->getCustomerAmbType());
        //NED-1419, add riki_course_id into order object for fraud check
        $order->setRikiCourseId($quote->getRikiCourseId());

        $taxRiki = $this->_taxHelper->getTaxRiki($quote);
        $order->setTaxRikiTotal($taxRiki);

        if (!$quote->getCustomerIsGuest()) {
            $customer = $quote->getCustomer();
            $sShoshaBusinessCode = $customer->getCustomAttribute('shosha_business_code');
            if ($sShoshaBusinessCode) {
                $order->setShoshaBusinessCode($sShoshaBusinessCode->getValue());
            }

            $membershipAttribute = $customer->getCustomAttribute('membership');
            if (!is_null($membershipAttribute)) {
                $order->setCustomerMembership($membershipAttribute->getValue());
            }
        }
    }
}