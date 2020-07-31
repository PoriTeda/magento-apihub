<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\SalesSequence;

use Magento\Framework\Model\ResourceModel\Db\Context as DatabaseContext;

class Meta extends \Magento\SalesSequence\Model\ResourceModel\Meta
{
    public function __construct(
        DatabaseContext $context,
        \Riki\Subscription\Model\Emulator\SalesSequence\MetaFactory $metaFactory,
        \Magento\SalesSequence\Model\ResourceModel\Profile $resourceProfile,
        $connectionName = null
    )
    {
        parent::__construct($context, $metaFactory, $resourceProfile, $connectionName);
    }
}
