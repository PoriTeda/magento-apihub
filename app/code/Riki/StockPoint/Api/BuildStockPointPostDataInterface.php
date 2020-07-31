<?php
namespace Riki\StockPoint\Api;

/**
 * @api
 */
interface BuildStockPointPostDataInterface
{
    /**
     * @return mixed
     */
    public function getSignValue();

    /**
     * @param $rawDataValue
     * @return mixed
     */
    public function setPostDataRequest($rawDataValue);

    /**
     * @return string
     */
    public function getPostDataRequestGenerate();

    /**
     * @param $b64ReqDataValue
     * @param $profileData
     * @return mixed
     */
    public function checkDataNotifyMapSelected($b64ReqDataValue, $profileData);

    /**
     * @param $profileId
     * @param $deliveryDate
     * @return int
     */
    public function getDiscountRate($profileId, $deliveryDate);

    /**
     * Validate request data stock point
     * @param $params array
     * @param $profileSession object
     * @return array
     */
    public function validateRequestStockPoint($params, $profileSession);

    /**
     * Remove subscription profile from bucket
     *
     * @param $profileId
     * @return array
     */
    public function removeFromBucket($profileId);
}
