<?php
namespace Riki\CatalogRule\Model;

class Product extends \Magento\Framework\DataObject implements \Riki\CatalogRule\Api\Data\ProductInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->setData(self::ID, $id);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAmount($amount)
    {
        $this->setData(self::AMOUNT, $amount);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAmountFormatted()
    {
        return $this->getData(self::AMOUNT_FORMATTED);
    }

    /**
     * {@inheritdoc}
     */
    public function setAmountFormatted($amount)
    {
        $this->setData(self::AMOUNT_FORMATTED, $amount);
        return $this;
    }
}
