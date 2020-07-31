<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Riki\Subscription\Model\Emulator\Config ;

class Cart extends \Magento\Quote\Model\ResourceModel\Quote
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        \Riki\Subscription\Model\Emulator\SalesSequence\Manager $sequenceManager,
        $connectionName = null
    )
    {
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $sequenceManager, $connectionName);
    }

    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( Config::getCartTmpTableName() , 'entity_id');
    }
}