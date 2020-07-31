<?php
namespace Riki\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RequestedMassAction extends AbstractDb
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_rma_action_queue', 'entity_id');
    }

    /**
     * @param $action
     * @param array $rmaIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRmaByActionRmaIds($action, array $rmaIds)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'rma_id')
            ->where('action=?', $action)
            ->where('rma_id IN (?)', $rmaIds);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param $action
     * @param $rmaId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMassActionItem($action, $rmaId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'rma_id')
            ->where('action=?', $action)
            ->where('rma_id = ?', $rmaId);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     * @param $action
     * @param array $rmaIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMassActionRmaIdByActionAndRmaIds($action, array $rmaIds)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'rma_id')
            ->where('action=?', $action)
            ->where('rma_id IN(?)', $rmaIds);

        return $this->getConnection()->fetchCol($select);
    }
}
