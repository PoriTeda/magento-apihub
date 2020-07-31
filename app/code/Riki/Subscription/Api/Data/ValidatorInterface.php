<?php
namespace Riki\Subscription\Api\Data;

interface ValidatorInterface
{
    /**
     * @return mixed
     */
    public function getProfileId();

    /**
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return mixed
     */
    public function getProductCarts();

    /**
     * @param array $productCarts
     * @return $this
     */
    public function setProductCarts(array $productCarts);

    /**
     * @return $this
     */
    public function getCourseId();

    /**
     * @param $courseId
     * @return $this
     */
    public function setCourseId($courseId);

    /**
     * @return mixed
     */
    public function isGenerate();

    /**
     * @param bool $isGenerate
     * @return $this
     */
    public function setIsGenerate(bool $isGenerate);

    /**
     * @return array
     */
    public function validateMaximumQtyRestriction();

    /**
     * @param array $productIds
     * @param $maxQty
     * @return mixed
     */
    public function getMessageMaximumError(array $productIds, $maxQty);

    /**
     * @param $listProducts
     * @return mixed
     */
    public function prepareProductData($listProducts);

    /**
     * @param $quote
     * @return mixed
     */
    public function prepareProductDataByQuote($quote);

    /**
     * @param $productErrors
     * @param $maxQty
     * @param $orderTimes
     * @return \Magento\Framework\Phrase
     */
    public function getMessageErrorValidateMaxQtyForGenerate($productErrors, $maxQty, $orderTimes);

    /**
     * @param $productPost
     * @param $quote
     * @return mixed|boolean
     */
    public function prepareProductDataForMultipleMachine($productPost, $quote);

    /**
     * Check skip validate quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return boolean
     */
    public function isItemSkipped(\Magento\Quote\Model\Quote\Item $item);
}