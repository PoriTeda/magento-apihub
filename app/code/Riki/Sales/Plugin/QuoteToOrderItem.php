<?php

namespace Riki\Sales\Plugin;

class QuoteToOrderItem
{
    public function __construct(
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionMachine
    )
    {
        $this->subscriptionMachine = $subscriptionMachine;
    }

    const FREEOFCHARGE = 1;
    const MACHINEPRODUCT = 1;

    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);

        $orderItem->setAddressId($item->getAddressId());
        $orderItem->setDeliveryDate($item->getDeliveryDate());
        $orderItem->setNextDeliveryDate($item->getNextDeliveryDate());
        $orderItem->setDeliveryTime($item->getDeliveryTime());
        $orderItem->setDeliveryTimeslotId($item->getDeliveryTimeslotId());
        $orderItem->setDeliveryTimeslotFrom($item->getDeliveryTimeslotFrom());
        $orderItem->setDeliveryTimeslotTo($item->getDeliveryTimeslotTo());
        $orderItem->setDistributionChannel($item->getDistributionChannel());
        $orderItem->setFocWbs($item->getFocWbs());
        $orderItem->setBookingWbs($item->getBookingWbs());
        $orderItem->setBookingAccount($item->getBookingAccount());
        $orderItem->setBookingCenter($item->getBookingCenter());
        $orderItem->setRulePrice($item->getRulePrice());
        $orderItem->setSalesOrganization($item->getSalesOrganization());

        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $item;

        $quote = $item->getQuote();
        if($quote){
            $productId = $item->getProduct()->getId() ;
            $courseId = $quote->getData('riki_course_id');
            if($productId && $courseId){
                $subscriptionMachineItem = $this->subscriptionMachine->getMachine($courseId,$productId);
            }
        }

        //convert riki taxes
        if($discountAmountExclTax = $quoteItem->getData('discount_amount_excl_tax')){
            $orderItem->setData('discount_amount_excl_tax', $discountAmountExclTax);
        }
        if($commissionAmount = $quoteItem->getData('commission_amount')) {
            $orderItem->setData('commission_amount', $commissionAmount);
        }
        if($taxRiki = $quoteItem->getData('tax_riki')) {
            $orderItem->setData('tax_riki', $taxRiki);
        }

        // convert machine
        $orderItem->setIsRikiMachine($item->getIsRikiMachine());

        /*get free of charge from quote item*/
        $freeOfCharge = $item->getFreeOfCharge();

        if ($freeOfCharge != self::FREEOFCHARGE) {
            /*free machine item -> set free of charge = 1 at order item level*/
            if ($item->getIsRikiMachine() == self::MACHINEPRODUCT) {

                $buyRequest = $item->getBuyRequest();
                $options = $buyRequest->getData('options');
                /*free machine auto - for AMB*/
                if ($options && isset($options['free_machine_item']) && $options['free_machine_item'] == self::MACHINEPRODUCT) {
                    $freeOfCharge = self::FREEOFCHARGE;
                } elseif (isset($subscriptionMachineItem['is_free']) && $subscriptionMachineItem['is_free'] !=0) {
                    /*free machine - select */
                    $freeOfCharge = self::FREEOFCHARGE;
                }
            }
        }

        /*set free of charge data for order item*/
        if ($freeOfCharge == self::FREEOFCHARGE) {
            $orderItem->setFreeOfCharge($freeOfCharge);
        }
        return $orderItem;
    }
}