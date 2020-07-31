<?php

namespace Riki\Checkout\Model;

use Magento\Framework\Model\AbstractExtensibleModel;

class CartSimulationTotals extends AbstractExtensibleModel implements \Riki\Checkout\Api\Data\CartSimulationTotalsInterface
{
    /**
     * @inheritdoc
     */
    public function setOrderTimes($orderTimes)
    {
        return $this->setData(self::ORDER_TIMES, $orderTimes);
    }

    /**
     * @inheritdoc
     */
    public function getOrderTimes()
    {
        return $this->getData(self::ORDER_TIMES);
    }

    /**
     * @inheritdoc
     */
    public function setGrandTotal($grandTotal)
    {
        return $this->setData(self::GRAND_TOTAL, $grandTotal);
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal()
    {
        return $this->getData(self::GRAND_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(
        \Riki\Checkout\Api\Data\CartSimulationTotalsExtensionInterface $extensionAttributes
    )
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
