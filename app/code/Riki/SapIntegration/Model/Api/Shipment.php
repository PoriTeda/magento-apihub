<?php

namespace Riki\SapIntegration\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Zend\Http\Response;
use Riki\SapIntegration\Logger\ShipmentDebugLogger;
use Riki\SapIntegration\Webapi\TransferFactory;

class Shipment
{
    const NO_NEED_TO_EXPORT = 0;
    const WAITING_FOR_EXPORT = 1;
    const EXPORTED_TO_SAP = 2;
    const FAILED_TO_EXPORT = 3;

    const FLAG_EXPORTED = 1;
    const FLAG_NEVER_EXPORT = 0;

    const BATCH_SHIPMENT = 'ORDERS';
    const BATCH_RMA = 'RETURNS';

    const XPATH_LOGIN = 'sap_integration_config/sap_environment/login';
    const XPATH_PASSWORD = 'sap_integration_config/sap_environment/password';
    const XPATH_ENDPOINT = 'sap_integration_config/sap_environment/endpoint';

    const XPATH_DEBUG = 'sap_integration_config/export_shipment/debug';
    const XPATH_SFTP_PATH = 'sap_integration_config/export_shipment/sftp';

    const XPATH_RMA_SFTP_PATH = 'sap_integration_config/export_rma/sftp';

    const XPATH_SFTP_IP = 'setting_sftp/setup_ftp/ftp_id';
    const XPATH_SFTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const XPATH_SFTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const XPATH_SFTP_PASSWORD = 'setting_sftp/setup_ftp/ftp_pass';

    const LOCAL_PATH = 'export_sap/shipment';
    const RMA_LOCAL_PATH = 'export_sap/rma';

    const PATH_SAP_REASON_CODE_REPLACEMENT_ORDER = 'sap_integration_config/sap_reason_code_shipment/sap_reason_code';
    const MATERIAL_TYPE_ALLOWED = ['FERT', 'HALB', 'UNBW'];
    const UNIT_ECOM_DEFAULT = 'EA';
    const SAP_REASON_CODE_DEFAULT = 'SD';
    const AMB_DISTRIBUTION_CHANNEL = '06';
    const AMB_OTHER_DISTRIBUTION_CHANNEL = '14';
    const AMB_SALES_ID = 3;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var ShipmentDebugLogger
     */
    protected $_logger;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var FixtureManager
     */
    protected $_fixtureManager;

    /**
     * @var Curl
     */
    protected $_httpAdapter;

    /**
     * @var Response
     */
    protected $_httpResponse;

    /**
     * @var \Riki\SapIntegration\Webapi\TransferFactory
     */
    protected $_transferFactory;

    /**
     * @var string
     */
    protected $_batchType = self::BATCH_SHIPMENT;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var File
     */
    protected $_file;

    /**
     * @var Sftp
     */
    protected $_sftp;

    /**
     * @var ReadFactory
     */
    protected $_readFactory;

    /**
     * Shipment constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ShipmentDebugLogger $logger
     * @param EncryptorInterface $encryptor
     * @param FixtureManager $fixtureManager
     * @param Curl $httpAdapter
     * @param Response $httpResponse
     * @param TransferFactory $transferFactory
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Sftp $sftp
     * @param ReadFactory $readFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ShipmentDebugLogger $logger,
        EncryptorInterface $encryptor,
        FixtureManager $fixtureManager,
        Curl $httpAdapter,
        Response $httpResponse,
        TransferFactory $transferFactory,
        DirectoryList $directoryList,
        File $file,
        Sftp $sftp,
        ReadFactory $readFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_encryptor = $encryptor;
        $this->_fixtureManager = $fixtureManager;
        $this->_httpAdapter = $httpAdapter;
        $this->_httpResponse = $httpResponse;
        $this->_transferFactory = $transferFactory;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_sftp = $sftp;
        $this->_readFactory = $readFactory;
    }

    /**
     * Set batch type RETURNS or SHIPMENT
     *
     * @param string $batchType
     * @return $this
     */
    public function setBatchType($batchType)
    {
        $this->_batchType = $batchType;
        return $this;
    }

    /**
     * Execute calling to SAP API
     *
     * @param \SoapVar $soapVar
     * @return array
     */
    public function exportToSAP(\SoapVar $soapVar)
    {
        $xmlRequest = $strResponse = '';
        try {
            $httpAdapter = $this->_initClient();
            $xmlRequest = $this->_parserXml($soapVar);
            $httpAdapter->write('POST', $this->_getEndpoint(), '1.1', $this->_getHeaders(), $xmlRequest);
            $strResponse = $httpAdapter->read();
            $objResponse = $this->_httpResponse->fromString($strResponse);
            if ($objResponse->getStatusCode() != Response::STATUS_CODE_202) {
                throw new LocalizedException(__($objResponse->getReasonPhrase()));
            }
            $result = ['error' => false];
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $result = ['error' => true, 'errorCode' => $e->getCode(), 'message' => $e->getMessage()];
        }
        if ($this->_isDebugEnable()) {
            $this->_logger->debug('REQUEST:');
            $this->_logger->debug($xmlRequest);
            $this->_logger->debug('RESPONSE:');
            $this->_logger->debug($strResponse);
        }
        return $result;
    }

    /**
     * call api to push xml request to SAP
     *
     * @param string $xmlRequest
     * @return array
     */
    public function exportToSapByXmlRequest($xmlRequest)
    {
        try {
            $this->_logger->info('Send request to Sap');

            $httpAdapter = $this->_initClient();
            $httpAdapter->write('POST', $this->_getEndpoint(), '1.1', $this->_getHeaders(), $xmlRequest);
            $strResponse = $httpAdapter->read();

            $this->_logger->info('Response: '. $strResponse);

            $objResponse = $this->_httpResponse->fromString($strResponse);
            if ($objResponse->getStatusCode() != Response::STATUS_CODE_202) {
                throw new LocalizedException(__($objResponse->getReasonPhrase()));
            }
            $result = ['error' => false];
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $result = ['error' => true, 'errorCode' => $e->getCode(), 'message' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Export xml to this folder (var)
     *
     * @return string
     */
    public function getLocalFolder()
    {
        if ($this->_batchType == self::BATCH_SHIPMENT) {
            return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::LOCAL_PATH;
        }
        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::RMA_LOCAL_PATH;
    }

    /**
     * Write XML file
     *
     * @param \SoapVar $soapVar
     * @param string $filename
     * @return array
     */
    public function exportToXML(\SoapVar $soapVar, $filename)
    {
        try {
            $result = ['error' => false];
            $xmlStr = $this->_parserXml($soapVar);
            $xmlFolder = $this->getLocalFolder();
            $this->_file->checkAndCreateFolder($xmlFolder);
            $xml = new \SimpleXMLElement($xmlStr);
            $xml->saveXML($xmlFolder . DIRECTORY_SEPARATOR . $filename);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $result = ['error' => true, 'errorCode' => $e->getCode(), 'message' => $e->getMessage()];
        }
        return $result;
    }

    /**
     * Send XML file to SFTP
     * @return void
     */
    public function sendExportedToSftp()
    {
        try {
            $xmlFolder = $this->getLocalFolder();
            $readObj = $this->_readFactory->create($xmlFolder);
            $xmlFiles = $readObj->read();
            $sfpPath = $this->_initSftp();
            if (!sizeof($xmlFiles)) {
                return;
            }
            foreach ($xmlFiles as $xmlFile) {
                $source = $readObj->getAbsolutePath($xmlFile);
                $uploaded = $this->_sftp->write($sfpPath.'/'.$xmlFile, $source);
                if ($uploaded) {
                    $this->_file->rm($source);
                }
            }
            $this->_sftp->close();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

    }

    /**
     * Connect SFTP
     *
     * @return string
     * @throws \Exception
     */
    protected function _initSftp()
    {
        $host = $this->_scopeConfig->getValue(self::XPATH_SFTP_IP);
        $port = $this->_scopeConfig->getValue(self::XPATH_SFTP_PORT);
        $username = $this->_scopeConfig->getValue(self::XPATH_SFTP_USER);
        $password = $this->_scopeConfig->getValue(self::XPATH_SFTP_PASSWORD);
        $this->_sftp->open(
            array(
                'host' => $host . ':' . $port,
                'username' => $username,
                'password' => $password,
                'timeout' => 300
            )
        );
        $homeFolder = $dirPath = $this->_sftp->pwd();
        if ($this->_batchType == self::BATCH_SHIPMENT) {
            $path = $this->_scopeConfig->getValue(self::XPATH_SFTP_PATH);
        } else {
            $path = $this->_scopeConfig->getValue(self::XPATH_RMA_SFTP_PATH);
        }
        $path = ltrim($path, '/');
        $dirList = explode('/', $path);
        foreach ($dirList as $dirItem) {
            $dirPath .= '/' . $dirItem;
            if (!$this->_sftp->cd($dirPath)) {
                $this->_sftp->mkdir($dirItem, 0777, false);
            }
            $this->_sftp->cd($dirPath);
        }
        if (!$this->_sftp->cd($homeFolder . '/' . $path)) {
            throw new LocalizedException(__('Can not create SFTP folder %1', $path));
        }
        return $this->_sftp->pwd();
    }

    /**
     * Init SOAP client for SAP API
     *
     * @return Curl
     */
    protected function _initClient()
    {
        $this->_httpAdapter->setConfig([
            'verifypeer' => false,
            'verifyhost' => false
        ]);
        return $this->_httpAdapter;
    }

    /**
     * Prepare API options
     *
     * @return array
     */
    protected function _getHeaders()
    {
        $login = $this->_scopeConfig->getValue(
            self::XPATH_LOGIN,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $password = $this->_scopeConfig->getValue(
            self::XPATH_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $password = $this->_encryptor->decrypt($password);
        $headers = [
            'Content-Type: text/xml;charset=UTF-8',
            'SOAPAction: "http://sap.com/xi/WebService/soap1.1"',
            'Authorization: Basic ' . base64_encode("$login:$password")
        ];

        return $headers;
    }

    /**
     * Convert SOAP vars to xml
     *
     * @param \SoapVar $soapVar
     * @return string
     */
    protected function _parserXml(\SoapVar $soapVar)
    {
        $options = [
            'soap_version' => SOAP_1_1
        ];
        $transferClient = $this->_transferFactory->create($this->_getWsdl(), $options);
        $transferClient->MI_DEVWR0035542_MagentoShipment($soapVar);
        return $transferClient->getXmlRequest();
    }

    /**
     * SAP service end point
     *
     * @return string
     */
    protected function _getEndpoint()
    {
        return $this->_scopeConfig->getValue(
            self::XPATH_ENDPOINT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get service wsdl
     *
     * @return string
     */
    protected function _getWsdl()
    {
        return $this->_fixtureManager->getFixture('Riki_SapIntegration::fixtures/wsdl/Order_RRQ.wsdl');
    }

    /**
     * Check is enable api logger
     *
     * @return boolean
     */
    protected function _isDebugEnable()
    {
        return $this->_scopeConfig->getValue(
            self::XPATH_DEBUG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
