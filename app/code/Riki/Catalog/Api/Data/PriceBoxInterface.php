<?php
namespace Riki\Catalog\Api\Data;

interface PriceBoxInterface
{
    const ID = 'id';
    const QTY = 'qty';
    const FINAL_PRICE = 'final_price';

    /**
     * Get product_id
     *
     * @return string|int
     */
    public function getId();

    /**
     * Set product_id
     *
     * @param $id
     *
     * @return \Riki\Catalog\Api\Data\PriceBoxInterface
     */
    public function setId($id);

    /**
     * Get qty
     *
     * @return string|int
     */
    public function getQty();

    /**
     * Set qty
     *
     * @param $qty
     *
     * @return \Riki\Catalog\Api\Data\PriceBoxInterface
     */
    public function setQty($qty);

    /**
     * Get final_price
     *
     * @return string|int
     */
    public function getFinalPrice();

    /**
     * Set final_price
     *
     * @param $finalPrice
     *
     * @return \Riki\Catalog\Api\Data\PriceBoxInterface
     */
    public function setFinalPrice($finalPrice);
}