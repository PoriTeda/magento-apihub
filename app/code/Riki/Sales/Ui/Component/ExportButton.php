<?php

namespace Riki\Sales\Ui\Component;

use \Riki\Sales\Helper\CheckRoleViewOnly;

/**
 * Class ExportButton
 */
class ExportButton
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Registry $coreRegistry,
        CheckRoleViewOnly $checkRoleOnly
    ) {
        $this->request       = $request;
        $this->registry      = $coreRegistry;
        $this->checkRoleOnly = $checkRoleOnly;
    }

    public function aroundPrepare($subject,\Closure $proceed)
    {
        $dataContext  = $subject->getContext();
        if($dataContext->getNamespace() =='sales_order_grid' || $dataContext->getNamespace() =='sales_order_shipment_grid' ){

            if ( $dataContext->getNamespace() =='sales_order_grid' && $subject->getName() =='export_button' )
            {
                if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::ORDER_GIRD_VIEW_ONLY))
                {
                    $subject->unsetData('name');
                    $subject->unsetData('config');
                }
            }

            if ( $dataContext->getNamespace() =='sales_order_shipment_grid' && $subject->getName() =='export_button' )
            {
                if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::SHIPMENT_GIRD_VIEW_ONLY))
                {
                    $subject->unsetData('name');
                    $subject->unsetData('config');
                }
            }

        }
        return $proceed($subject);
    }

}
