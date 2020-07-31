<?php


namespace Riki\Wamb\Api\Data;

interface RegisterInterface
{
    const REGISTER_ID = 'register_id';
    const CUSTOMER_ID = 'customer_id';
    const CONSUMER_DB_ID = 'consumer_db_id';
    const STATUS = 'status';
    const ORDER_ID = 'order_id';
    const RULE_ID = 'rule_id';

    /**
     * Get register_id
     *
     * @return string|null
     */
    public function getRegisterId();

    /**
     * Set register_id
     *
     * @param $registerId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setRegisterId($registerId);

    /**
     * Get customer_id
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param string $customerId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setCustomerId($customerId);

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
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setConsumerDbId($consumer_db_id);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setStatus($status);

    /**
     * Get rule_id
     *
     * @return string|null
     */
    public function getRuleId();

    /**
     * Set rule_id
     *
     * @param $ruleId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setRuleId($ruleId);

    /**
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param $orderId
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function setOrderId($orderId);
}
