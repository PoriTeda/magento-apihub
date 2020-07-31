<?php
namespace Riki\ShippingCarrier\Model\Carrier;


class Dummy extends \Riki\ShippingCarrier\Model\Carrier\BaseCarrier
{
    const CARRIER_CODE = 'dummy';
    /**
     * @var string
     */
    protected $_code = 'dummy';
}