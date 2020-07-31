<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel;

use Magento\Rma\Model\Spi\RmaResourceInterface;

/**
 * RMA entity resource model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Rma extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements RmaResourceInterface
{
    /**
     * Rma grid factory
     *
     * @var \Magento\Rma\Model\GridFactory
     */
    protected $rmaGridFactory;

    /**
     * Eav configuration model
     *
     * @var \Magento\SalesSequence\Model\Manager
     */
    protected $sequenceManager;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Rma\Model\GridFactory $rmaGridFactory
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Rma\Model\GridFactory $rmaGridFactory,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        $connectionName = null
    ) {
        $this->rmaGridFactory = $rmaGridFactory;
        $this->sequenceManager = $sequenceManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_rma', 'entity_id');
    }

    /**
     * Perform actions after object save
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        /** @var \Magento\Rma\Model\Rma $object */
        /** @var $gridModel \Magento\Rma\Model\Grid */
        $gridModel = $this->rmaGridFactory->create();
        $gridModel->addData($object->getData());
        $gridModel->save();

        $itemsCollection = $object->getItems();
        if (is_array($itemsCollection)) {
            foreach ($itemsCollection as $item) {
                $item->save();
            }
        }

        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeSave($object);
        /** @var \Magento\Rma\Model\Rma $object */
        if (!$object->getIncrementId()) {
            $incrementId = $this->sequenceManager->getSequence('rma_item', $object->getStoreId())->getNextValue();
            $object->setIncrementId($incrementId);
        }
        if (!$object->getIsUpdate()) {
            $object->setData(
                'protect_code',
                substr(
                    md5(uniqid(\Magento\Framework\Math\Random::getRandomNumber(), true) . ':' . microtime(true)),
                    5,
                    6
                )
            );
        }

        return $this;
    }
}
