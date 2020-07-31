<?php
namespace Riki\Prize\Api\Data;

interface PrizeInterface
{
    const CONSUMER_DB_ID = 'consumer_db_id';
    const SKU = 'sku';
    const WBS = 'wbs';
    const QTY = 'qty';
    const STATUS = 'status';
    const WINNING_DATE = 'winning_date';
    const ORDER_NO = 'order_no';
    const CAMPAIGN_CODE = 'campaign_code';
    const MAIL_SEND_DATE = 'mail_send_date';
    const ORM_ID = 'orm_id';

    /**
     * Get consumer_db_id
     *
     * @return string|null
     */
    public function getConsumerDbId();

    /**
     * Set consumer_db_id
     *
     * @param $consumerDbId
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setConsumerDbId($consumerDbId);

    /**
     * Get sku
     *
     * @return string|null
     */
    public function getSku();

    /**
     * Set sku
     *
     * @param $sku
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setSku($sku);

    /**
     * Get wbs
     *
     * @return string|null
     */
    public function getWbs();

    /**
     * Set wbs
     *
     * @param $wbs
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setWbs($wbs);

    /**
     * Get qty
     *
     * @return string|null
     */
    public function getQty();

    /**
     * Set qty
     *
     * @param $qty
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setQty($qty);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param $status
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setStatus($status);

    /**
     * Get winning_date
     *
     * @return string|null
     */
    public function getWinningDate();

    /**
     * Set winning_date
     *
     * @param $winningDate
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setWinningDate($winningDate);

    /**
     * Get order_no
     *
     * @return string|null
     */
    public function getOrderNo();

    /**
     * Set order_no
     *
     * @param $orderNo
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setOrderNo($orderNo);

    /**
     * Get campaign_code
     *
     * @return string|null
     */
    public function getCampaignCode();

    /**
     * Set campaign_code
     *
     * @param $campaignCode
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setCampaignCode($campaignCode);

    /**
     * Get mail_send_date
     *
     * @return string|null
     */
    public function getMailSendDate();

    /**
     * Set mail_send_date
     *
     * @param $mailSendDate
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function setMailSendDate($mailSendDate);

    /**
     * Get orm_id
     *
     * @return string|null
     */
    public function getOrmId();

    /**
     * Set orm_id
     *
     * @param $ormId
     *
     * @return string|null
     */
    public function setOrmId($ormId);
}