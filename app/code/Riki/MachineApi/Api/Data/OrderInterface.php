<?php
namespace Riki\MachineApi\Api\Data;

interface OrderInterface
{
    /**#@+
     * Constants defined for keys of  data array
     */
    const ORDER_ID = 'orderID';


    /**
     * Order id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order id
     *
     * @param  $orderID
     * @return $this
     */
    public function setOrderId($orderID);

    



}
