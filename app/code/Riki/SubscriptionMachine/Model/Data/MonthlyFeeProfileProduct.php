<?php

namespace Riki\SubscriptionMachine\Model\Data;

use \Magento\Framework\DataObject;
use \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileProductInterface;

class MonthlyFeeProfileProduct extends DataObject implements MonthlyFeeProfileProductInterface
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
}
