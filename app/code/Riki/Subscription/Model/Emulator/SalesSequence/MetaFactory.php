<?php

namespace Riki\Subscription\Model\Emulator\SalesSequence;

class MetaFactory extends \Magento\SalesSequence\Model\MetaFactory
{
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Riki\\Subscription\\Model\\Emulator\\SalesSequence\\Meta')
    {
        parent::__construct($objectManager, $instanceName);
    }
}
