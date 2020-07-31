<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment;

class SapTransfer
{
    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\SapTransfer
     */
    protected $ordersSapTransfer;

    /**
     * SapTransfer constructor.
     *
     * @param \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\SapTransfer $orderSapTransfer
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Orders\SapTransfer $orderSapTransfer,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ) {
        $this->ordersSapTransfer = $orderSapTransfer;
        $this->scopeConfigHelper = $scopeConfigHelper;
    }

    /**
     * Pass failed records
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     *
     * @return array
     */
    public function afterExport(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        foreach ($this->ordersSapTransfer->getFailedIds() as $id) {
            $subject->trackFailed($id);
        }

        return $result;
    }
}
