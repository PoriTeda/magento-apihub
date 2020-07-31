<?php
namespace Riki\MachineApi\Model;

class Order extends \Magento\Framework\DataObject implements \Riki\MachineApi\Api\Data\OrderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderId($orderID)
    {
        $this->setData(self::ORDER_ID, $orderID);

        return $this;
    }


}
