<?php

namespace Nestle\Fraud\Plugin\Model\Rule\Condition;

class Order
{
    public function afterLoadAttributeOptions(\Mirasvit\FraudCheck\Model\Rule\Condition\Order $order, $result)
    {
        $a = 1;
        $options = $result->getAttributeOption();
        $options['subscription_course'] = __('Subscription Course');
        $result->setAttributeOption($options);

        return $result;
    }

}