<?php

namespace Nestle\Gillette\Model\Data;

use Magento\Framework\DataObject;
use Nestle\Gillette\Api\Data\ProductInfoInterface;

Class ProductInfo extends DataObject implements ProductInfoInterface
{
    /**
     * {@inheritdoc}
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * {@inheritdoc}
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftWrapId($giftWrapId) {
        return $this->setData(self::GW_ID,$giftWrapId);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftWrapId(){
        return $this->getData(self::GW_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsMachine($isMachine)
    {
        return $this->setData(self::IS_MACHINE, $isMachine);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsMachine()
    {
        return $this->getData(self::IS_MACHINE);
    }
}
