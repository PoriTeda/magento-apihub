<?php

namespace Riki\Fraud\Model\Rule\Condition;

class Billing extends \Mirasvit\FraudCheck\Model\Rule\Condition\Billing
{
    const PAYMENT_ATTRIBUTE = 'payment_method';

    /**
     * Default function forgot to add payment_method data for Billing data
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $object)
    {
        $paymentMethod = '';

        if (!empty( $object->getPayment() )) {
            $paymentMethod = $object->getPayment()->getMethodInstance()->getCode();
        }

        if ($this->getAttribute() === self::PAYMENT_ATTRIBUTE) {
            $rs = $this->validateAttribute( $paymentMethod );
        } else {
            $rs = parent::validate($object);
        }

        return $rs;
    }
}
