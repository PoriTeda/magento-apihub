<?php 
namespace Riki\Customer\Model;

class ConsumerLogFile  extends \Magento\Framework\DataObject
{
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 0;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Riki\Customer\Logger\ConsumerLog\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ConsumerLogFile constructor.
     *
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Riki\Customer\Logger\ConsumerLog\Logger $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Riki\Customer\Logger\ConsumerLog\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_jsonHelper = $jsonHelper;
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /*add log data to file*/
    public function save()
    {
        $isLogging = (int) $this->scopeConfig->getValue('consumer_db_api_url/logging_kss_api/enableLoggingApiCall',\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if(!$isLogging){
            return;
        }
        if (!empty($this->_data)) {
            $this->_logger->info($this->_jsonHelper->jsonEncode($this->_data));
            return true;
        }
        return false;
    }
}