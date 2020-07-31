<?php
namespace Riki\Wamb\Model\ResourceModel;

use Riki\Wamb\Api\Data\Register\StatusInterface;

class Register extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('riki_wamb_register', 'register_id');
    }

    /**
     * Get waiting wamb customer ids
     *
     * @return array
     */
    public function getWaitingWambCustomerIds()
    {
        $conn = $this->getConnection();
        $select = $conn->select()->from($conn->getTableName('riki_wamb_register'), ['consumer_db_id', 'customer_id'])
            ->where('status IN (?)', [StatusInterface::WAITING, StatusInterface::ERROR])
            ->distinct(true);

        return $conn->fetchPairs($select);
    }
}
