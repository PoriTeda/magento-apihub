<?php

namespace Riki\MachineApi\Plugin;

class RemoveCatalogRuleForMachineMaintenance
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * RemoveCatalogRuleForMachineMaintenance constructor.
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request
    )
    {
        $this->_request = $request;
    }

    public function aroundGetRulePrice(
        \Magento\CatalogRule\Model\ResourceModel\Rule $subject,
        \Closure $proceed,
        $date, $wId, $gId, $pId
    )
    {
        $result = $proceed($date, $wId, $gId, $pId);

        //check request from api
        $params = $this->_request->getParams();
        if (isset($params['call_machine_api']) || isset($params['data_machine_api'])) {
            return false;
        }

        return $result;
    }
}
