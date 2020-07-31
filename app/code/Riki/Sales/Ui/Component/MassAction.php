<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Ui\Component;

use \Riki\Sales\Helper\CheckRoleViewOnly;
/**
 * Class MassAction
 */
class MassAction
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    protected $authorization;
    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Registry $coreRegistry,
        CheckRoleViewOnly $checkRoleOnly
    ) {
        $this->request = $request;
        $this->registry = $coreRegistry;
        $this->checkRoleOnly = $checkRoleOnly;
    }


    /**
     * Remove button cancel order on orders gird
     *
     * @param \Magento\Ui\Component\MassAction $subject
     *
     * @return \Magento\Ui\Component\MassAction
     */
    public function aroundPrepare($subject,\Closure $proceed)
    {
        $dataContext  = $subject->getContext();

        if($dataContext->getNamespace() =='sales_order_grid' || $dataContext->getNamespace() =='sales_order_shipment_grid' ){

            //remove  mass action shipment
            if ( $dataContext->getNamespace() =='sales_order_shipment_grid')
            {
                if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::SHIPMENT_GIRD_VIEW_ONLY))
                {
                    $subject->unsetData('name');
                    $subject->unsetData('config');
                }
            }

            //remove aon mass action sales order
            if ( $dataContext->getNamespace() =='sales_order_grid')
            {
                if ($this->checkRoleOnly->checkViewShipmentOnly( CheckRoleViewOnly::ORDER_GIRD_VIEW_ONLY ))
                {
                    $subject->unsetData('name');
                    $subject->unsetData('config');
                }
            }

            //remove button cancel on sale order
            $config = $subject->getConfiguration();
            foreach ($subject->getChildComponents() as $actionComponent) {
                if($actionComponent->getData('name') !='cancel'){
                    $config['actions'][] = $actionComponent->getConfiguration();
                }
            }

            $origConfig = $subject->getConfiguration();
            if ($origConfig !== $config) {
                $config = array_replace_recursive($config, $origConfig);
            }

            $subject->setData('config', $config);

        }else{
            return $proceed($subject);
        }

    }

}
