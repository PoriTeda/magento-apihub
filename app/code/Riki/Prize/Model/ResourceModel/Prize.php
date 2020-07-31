<?php
namespace Riki\Prize\Model\ResourceModel;

class Prize extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ){
        parent::__construct($context, $connectionName);
    }
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_prize', 'prize_id');
    }

    /**
     * Check if prize is existed with product sku and customer id
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prizeExisted(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from(
            $this->getMainTable(),
            ['total' => new \Zend_Db_Expr('COUNT("prize_id")')]
        )->where('consumer_db_id = ?', $object->getData('consumer_db_id')
        )->where('sku = ?', $object->getData('sku')
        )->where('campaign_code = ?', $object->getData('campaign_code'));
        if ($object->getId()) {
            $sqlSelect->where('prize_id NOT IN(?)', $object->getId());
        }
        return (int) $connection->fetchOne($sqlSelect);
    }
}