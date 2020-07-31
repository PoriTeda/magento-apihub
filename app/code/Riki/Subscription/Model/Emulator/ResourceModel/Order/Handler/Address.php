<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Handler;

use Riki\Subscription\Model\Emulator\ResourceModel\Attribute;

class Address
    extends \Magento\Sales\Model\ResourceModel\Order\Handler\Address
{
    public function __construct(Attribute $attribute)
    {
        parent::__construct($attribute);
    }
}