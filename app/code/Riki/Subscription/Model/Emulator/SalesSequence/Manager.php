<?php

namespace Riki\Subscription\Model\Emulator\SalesSequence;

use Riki\Subscription\Model\Emulator\ResourceModel\SalesSequence\Meta as ResourceSequenceMeta;
use Magento\SalesSequence\Model\SequenceFactory;

class Manager extends \Magento\SalesSequence\Model\Manager
{
    public function __construct(
        ResourceSequenceMeta $resourceSequenceMeta,
        SequenceFactory $sequenceFactory
    )
    {
        parent::__construct($resourceSequenceMeta, $sequenceFactory);
    }
}
