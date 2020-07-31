<?php
namespace Riki\Customer\Helper;

class ConsumerLog extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Riki\Customer\Model\ConsumerLogFile
     */
    protected $consumerLog;


    /**
     * ConsumerLog constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Customer\Model\ConsumerLogFile $consumerLog
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Customer\Model\ConsumerLogFile $consumerLog
    ) {
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->consumerLog = $consumerLog;
        parent::__construct($context);
    }

    /**
     * Save consumer API log
     *
     * @param string $apiName
     * @param string $description
     * @param boolean $status
     * @param string $request
     * @param array|string $responseData
     *
     * @return \Exception|\Magento\Framework\Phrase
     */
    public function saveAPILog($apiName, $description, $status, $request, $responseData,$isSimulator = false)
    {
        $isLogging = (int) $this->scopeConfig->getValue('consumer_db_api_url/logging_kss_api/enableLoggingApiCall',\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if(!$isLogging){
            return;
        }
        if($isSimulator){
            return;
        }
        $modelConsumerLog = $this->consumerLog;
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $timeNow = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $data = [
            'name' => $apiName,
            'description' => $description,
            'status' => $status,
            'date' => $timeNow,
            'request' => $request,
            'response_data' => is_array($responseData) ? \Zend_Json::encode($responseData) :
                $responseData
        ];
        $modelConsumerLog->setData($data);
        try {
            if ($modelConsumerLog->save()) {
                return __('The consumer api log save success.');
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return $e;
        }
        return __('The consumer api log save fail.');
    }
}