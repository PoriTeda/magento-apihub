<?php
namespace Riki\CatalogRule\Api\Data;

/**
 * @api
 */
interface ProductInterface
{
    const ID = 'id';
    const AMOUNT = 'amount';
    const AMOUNT_FORMATTED = 'amount_formatted';

    /**
     * Get product id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set product id
     *
     * @param $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get product amount
     *
     * @return float|null
     */
    public function getAmount();

    /**
     * Set product amount
     *
     * @param $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Get product amount with currency
     * @return string
     */
    public function getAmountFormatted();

    /**
     * Set product amount with currency
     * @param $amount
     * @return $this
     */
    public function setAmountFormatted($amount);
}
