<?php

namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Exporter\Returns;

class SapTransfer
{
    /**
     * @var array
     */
    protected $failedIds = [];
    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;
    /**
     * @var \Riki\SapIntegration\Model\Api\Shipment
     */
    protected $soapApi;

    /**
     * SapTransfer constructor.
     *
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\SapIntegration\Model\Api\Shipment $soapApi
     */
    public function __construct(
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\SapIntegration\Model\Api\Shipment $soapApi
    ) {
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->soapApi = $soapApi;
    }

    /**
     * Get failed ids
     *
     * @return array
     */
    public function getFailedIds()
    {
        return $this->failedIds;
    }

    /**
     * after export to xml, get xml request and push to api
     *
     * @param \Riki\SapIntegration\Cron\Exporter\Returns
     * @param $result
     *
     * @return mixed
     */
    public function afterExportToXml(\Riki\SapIntegration\Cron\Exporter\Returns $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        $subject->addLogMessage('Send request to SAP:');

        $exportStatus = false;

        /*export xml request to sap*/
        $exportToSap = $this->soapApi->exportToSapByXmlRequest($result);

        /*cannot export to SAP, tracking error id and revert data*/
        if (!empty($exportToSap) && $exportToSap['error'] == true) {
            $subject->addLogMessage('Send request to SAP failed.');
            $this->failedIds = array_unique(array_merge($this->failedIds, array_keys($subject->getBatchIds())));
        } else {
            $exportStatus = true;
            $subject->addLogMessage('Send request to SAP success.');
        }

        if ($this->canCreateBackupFile($exportStatus)) {
            /*after export to SAP success, create backup data - xml file*/
            $subject->createBackupXml($result);
        }

        /*reset exported data*/
        $subject->resetExportedData();

        return $result;
    }

    /**
     * can create backup file after export to SAP (based on exportStatus)
     *
     * @param $exportStatus
     * @return bool
     */
    public function canCreateBackupFile($exportStatus)
    {
        if (!$exportStatus) {
            return false;
        }

        return true;
    }
}