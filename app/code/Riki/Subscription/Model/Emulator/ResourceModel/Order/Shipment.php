<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Shipment extends \Magento\Sales\Model\ResourceModel\Order\Shipment
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Sales\Model\ResourceModel\Attribute $attribute,
        \Riki\Subscription\Model\Emulator\SalesSequence\Manager $sequenceManager,
        $connectionName = null
    ) {
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $attribute, $sequenceManager,$connectionName);
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getShipmentTmpTableName(), 'entity_id');
    }

    /**
     * Perform actions before object save
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $object */
        if ((!$object->getId() || null !== $object->getItems()) && !count($object->getAllItems())) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot create an empty shipment.'));
        }

        if (!$object->getOrderId() && $object->getOrder()) {
            $object->setOrderId($object->getOrder()->getId());
            $object->setShippingAddressId($object->getOrder()->getShippingAddress()->getId());
        }

        if ($object instanceof \Magento\Sales\Model\EntityInterface && $object->getIncrementId() == null) {
            $object->setIncrementId(
                $this->sequenceManager->getSequence(
                    $object->getEntityType(),
                    $object->getStore()->getGroup()->getDefaultStoreId()
                )->getNextValue()
            );
        }

        return parent::_beforeSave($object);
    }
}