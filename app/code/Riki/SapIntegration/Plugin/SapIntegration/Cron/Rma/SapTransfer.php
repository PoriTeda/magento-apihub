<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Rma;

use Riki\SapIntegration\Api\ConfigInterface;

class SapTransfer
{
    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;
    /**
     * @var \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Returns\SapTransfer
     */
    protected $returnsSapTransfer;

    /**
     * SapTransfer constructor.
     *
     * @param \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Returns\SapTransfer $returnsSapTransfer
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Returns\SapTransfer $returnsSapTransfer,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ) {
        $this->returnsSapTransfer = $returnsSapTransfer;
        $this->scopeConfigHelper = $scopeConfigHelper;
    }

    /**
     * Pass failed records
     *
     * @param \Riki\SapIntegration\Cron\RmaV2 $subject
     * @param $result
     *
     * @return array
     */
    public function afterExport(\Riki\SapIntegration\Cron\RmaV2 $subject, $result)
    {
        foreach ($this->returnsSapTransfer->getFailedIds() as $id) {
            $subject->trackFailed($id);
        }

        return $result;
    }
}
