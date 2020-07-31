<?php

namespace Riki\Sales\Helper;

use Magento\Framework\AuthorizationInterface;

class CheckRoleViewOnly extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SHIPMENT_GIRD_VIEW_ONLY = 'Riki_Sales::shipmentOrderGirdOnly';
    const SHIPMENT_VIEW_ONLY = 'Riki_Sales::shipmentViewOrderOnly';

    const ORDER_GIRD_VIEW_ONLY = 'Riki_Sales::salesOrderGirdOnly';
    const ORDER_VIEW_ONLY = 'Riki_Sales::salesViewOrderOnly';

    const ALL_ROLE = 'Magento_Backend::all';

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    public function __construct(
        AuthorizationInterface $authorization,
        \Magento\Framework\App\Helper\Context $context

    ) {
        $this->authorization = $authorization;
        parent::__construct($context);
    }

    public function checkViewShipmentOnly($roleName)
    {
        if ($this->authorization->isAllowed(CheckRoleViewOnly::ALL_ROLE)) {
            return false;
        } elseif ($this->authorization->isAllowed($roleName)) {
            return true;
        }

        return false;
    }
}