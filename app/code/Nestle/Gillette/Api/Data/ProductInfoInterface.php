<?php

namespace Nestle\Gillette\Api\Data;

interface ProductInfoInterface
{
    const SKU = 'sku';
    const QTY = 'qty';
    const GW_ID = 'gw_id';
    const IS_MACHINE = 'is_machine';

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * @return string
     */
    public function getSku();

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * @return int
     */
    public function getQty();

    /**
     * @param int $giftWrapId
     * @return $this
     */
    public function setGiftWrapId($giftWrapId);

    /**
     * @return int|null
     */
    public function getGiftWrapId();

    /**
     * @param bool $isMachine
     * @return $this
     */
    public function setIsMachine($isMachine);

    /**
     * @return mixed
     */
    public function getIsMachine();
}
