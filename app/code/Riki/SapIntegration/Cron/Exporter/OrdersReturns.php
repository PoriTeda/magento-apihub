<?php
namespace Riki\SapIntegration\Cron\Exporter;

use Riki\SapIntegration\Api\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class OrdersReturns extends \Riki\SapIntegration\Cron\Exporter\Orders
{
    /**
     * @inheritdoc
     *
     * @return void
     */
    public function clean()
    {
        if ($this->fileName) {
            $this->exportToXml();
        }

        /*reset exported Data*/
        $this->resetExportedData();

        $localDirectory = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->local();
        $localDirectory = $localDirectory ? $localDirectory : 'Riki_SapIntegration/Cron/Shipment';
        $localDirectory = $localDirectory . DIRECTORY_SEPARATOR . $this->datetimeHelper->getToday()->format('Y-m-d');
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create($localDirectory);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath($localDirectory);
        $this->fileName = 'RETURNS_' . $this->datetimeHelper->getToday()->format('Y-m-d_H-i-s') . '.xml';
    }

    /**
     * {@inheritdoc}
     *
     * @return null|string
     */
    public function exportToXml()
    {
        if ($this->batchData) {
            $params = [];
            $params[] = new \SoapVar('RETURNS', XSD_STRING, null, null, 'ShipmentBatchType');
            foreach ($this->batchData as $shipmentData) {
                $soapShipment = [];
                foreach ($shipmentData as $field => $value) {
                    $soapShipment[] = new \SoapVar($value, XSD_STRING, null, null, $field);
                }
                $soapShipmentVar = new \SoapVar($soapShipment, SOAP_ENC_OBJECT, null, null, 'MagentoShipment');
                $params[] = new \SoapVar($soapShipmentVar, SOAP_ENC_ARRAY);
            }

            /*convert soap var to xml request*/
            return $this->convertSoapVarToXml(new \SoapVar($params, SOAP_ENC_OBJECT));
        }

        return null;
    }
}
