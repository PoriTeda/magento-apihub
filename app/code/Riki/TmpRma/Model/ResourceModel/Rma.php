<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db;

/**
 * Class Rma
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Rma extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * RmaItemFactory
     *
     * @var \Riki\TmpRma\Model\Rma\ItemFactory
     */
    protected $rmaItemFactory;

    /**
     * Rma constructor.
     *
     * @param Db\Context                         $context        context
     * @param \Riki\TmpRma\Model\Rma\ItemFactory $itemFactory    factory
     * @param null                               $connectionName param
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Riki\TmpRma\Model\Rma\ItemFactory $itemFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->rmaItemFactory = $itemFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('riki_tmprma', 'id');
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Model\AbstractModel $object object
     *
     * @return mixed
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object) //@codingStandardsIgnoreLine
    {
        if ($object->getId()) {
            $object->setIsUpdate(true);
        }

        return parent::_beforeSave($object);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Model\AbstractModel $object object
     *
     * @return mixed
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object) //@codingStandardsIgnoreLine
    {
        $newItems = $object->getItems();
        if ($newItems) {
            if ($object->getIsUpdate()) {
                $existingItems = $object->getItemsCollection()->getItems();
                $getIdFunc = function ($item) {
                    return $item->getId();
                };
                $existingItemIds = array_map($getIdFunc, $existingItems);
            } else {
                $existingItemIds = [];
            }

            $savedItemIds = [];
            foreach ($object->getItems() as $itemData) {
                $itemObject = $this->rmaItemFactory->create();

                if (isset($itemData['id'])) {
                    if (!in_array($itemData['id'], $existingItemIds)) {
                        continue;
                    }
                    $itemObject->load($itemData['id']);
                }

                $itemObject->addData($itemData);

                $itemObject->setParentId($object->getId())->save();
                if ($itemObject->getId()) {
                    $savedItemIds[] = $itemObject->getId();
                }
            }

            $itemIdsToDelete = array_diff($existingItemIds, $savedItemIds);
            foreach ($itemIdsToDelete as $itemId) {
                $this->rmaItemFactory->create()->load($itemId)->delete();
            }
        }

        return parent::_afterSave($object);
    }
}
