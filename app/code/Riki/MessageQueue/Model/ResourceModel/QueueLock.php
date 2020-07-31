<?php

namespace Riki\MessageQueue\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Riki\MessageQueue\Model\Consumer\Failure;

class QueueLock extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_setMainTable('riki_message_queue_lock');
    }

    /**
     * @param $topicName
     * @param $entityId
     * @param null $publishedBy
     * @return QueueLock
     * @throws LocalizedException
     */
    public function lock($topicName, $entityId, $publishedBy = null)
    {
        return $this->lockMultipleCode($topicName, [$entityId], $publishedBy);
    }

    /**
     * @param $topicName
     * @param array $entityIds
     * @param null $publishedBy
     * @return $this
     * @throws LocalizedException
     */
    public function lockMultipleCode($topicName, array $entityIds, $publishedBy = null)
    {
        $insertedData = [];

        foreach ($entityIds as $entityId) {
            $insertedData[] = [
                'topic_name' => $topicName,
                'entity_id' => $entityId,
                'pushed_by' => $publishedBy
            ];
        }

        $this->getConnection()
            ->insertMultiple(
                $this->getMainTable(),
                $insertedData
            );

        return $this;
    }

    /**
     * @param $topicName
     * @param $entityId
     * @param null $publishedBy
     * @return $this
     * @throws LocalizedException
     */
    public function deleteLock($topicName, $entityId, $publishedBy = null)
    {
        $where = [
            'topic_name = ?' => $topicName,
            'entity_id = ?' => $entityId
        ];

        if ($publishedBy) {
            $where['pushed_by = ?'] = $publishedBy;
        }

        $this->getConnection()
            ->delete(
                $this->getMainTable(),
                $where
            );

        return $this;
    }

    /**
     * @param $topicName
     * @return array
     * @throws LocalizedException
     */
    public function getInProcessingMessages($topicName)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('topic_name = ?', $topicName);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param $executor
     * @return array
     * @throws LocalizedException
     */
    public function getInProcessingFailureMessages($executor)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('topic_name = ?', Failure::FAILURE_TOPIC_NAME)
            ->where('pushed_by = ?', $executor);

        return $this->getConnection()->fetchCol($select);
    }
}
