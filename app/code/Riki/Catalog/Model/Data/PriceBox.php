<?php

namespace Riki\Catalog\Model\Data;

class PriceBox extends \Magento\Framework\DataObject implements \Riki\Catalog\Api\Data\PriceBoxInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $id
     *
     * @return \Riki\Catalog\Model\Data\PriceBox
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param $qty
     *
     * @return \Riki\Catalog\Model\Data\PriceBox
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getFinalPrice()
    {
        return $this->getData(self::FINAL_PRICE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $finalPrice
     *
     * @return \Riki\Catalog\Model\Data\PriceBox
     */
    public function setFinalPrice($finalPrice)
    {
        return $this->setData(self::FINAL_PRICE, $finalPrice);
    }
}