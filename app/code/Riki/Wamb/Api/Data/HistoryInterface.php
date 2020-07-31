<?php


namespace Riki\Wamb\Api\Data;

interface HistoryInterface
{

    const DETAIL = 'detail';
    const MESSAGE = 'message';
    const HISTORY_ID = 'history_id';
    const CUSTOMER_ID = 'customer_id';
    const EVENT = 'event';
    const CONSUMER_DB_ID = 'consumer_db_id';
    const CREATED_AT = 'created_at';


    /**
     * Get history_id
     *
     * @return string|null
     */
    public function getHistoryId();

    /**
     * Set history_id
     *
     * @param string $history_id
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setHistoryId($history_id);

    /**
     * Get customer_id
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param string $customer_id
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setCustomerId($customer_id);

    /**
     * Get consumer_db_id
     *
     * @return string|null
     */
    public function getConsumerDbId();

    /**
     * Set consumer_db_id
     *
     * @param string $consumer_db_id
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setConsumerDbId($consumer_db_id);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $created_at
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setCreatedAt($created_at);

    /**
     * Get event
     *
     * @return string|null
     */
    public function getEvent();

    /**
     * Set event
     *
     * @param string $event
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setEvent($event);

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setMessage($message);

    /**
     * Get detail
     *
     * @return string|null
     */
    public function getDetail();

    /**
     * Set detail
     *
     * @param string $detail
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function setDetail($detail);
}
