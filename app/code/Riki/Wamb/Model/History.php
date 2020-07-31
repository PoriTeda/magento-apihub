<?php
namespace Riki\Wamb\Model;


class History extends \Magento\Framework\Model\AbstractModel implements \Riki\Wamb\Api\Data\HistoryInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\ResourceModel\History::class);
    }

    /**
     * Get history_id
     *
     * @return string
     */
    public function getHistoryId()
    {
        return $this->getData(self::HISTORY_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $historyId
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setHistoryId($historyId)
    {
        return $this->setData(self::HISTORY_ID, $historyId);
    }

    /**
     * Get customer_id
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     *
     * @param string $customerId
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     *
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getConsumerDbId()
    {
        return $this->getData(self::CONSUMER_DB_ID);
    }

    /**
     * @param string $consumerDbId
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setConsumerDbId($consumerDbId)
    {
        return $this->setData(self::CONSUMER_DB_ID, $consumerDbId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $createdAt
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->getData(self::EVENT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $event
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setEvent($event)
    {
        return $this->setData(self::EVENT, $event);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->getData(self::DETAIL);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $detail
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setDetail($detail)
    {
        return $this->setData(self::DETAIL, $detail);
    }
}
