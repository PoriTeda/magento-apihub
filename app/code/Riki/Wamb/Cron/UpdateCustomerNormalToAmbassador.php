<?php
namespace Riki\Wamb\Cron;

use Riki\Wamb\Api\Data\History\EventInterface;
use Riki\Wamb\Api\Data\Register\StatusInterface;

class UpdateCustomerNormalToAmbassador
{
    protected $customerData;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository
     */
    protected $ambCustomerRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $timezoneInterface;

    /**
     * @var \Riki\Wamb\Model\RegisterRepository
     */
    protected $registerRepository;

    /**
     * @var \Riki\Wamb\Helper\ConfigData
     */
    protected $configDataHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Riki\Framework\Helper\Logger\Monolog
     */
    protected $loggerHelper;

    /**
     * UpdateCustomerNormalToAmbassador constructor.
     *
     * @param \Riki\Wamb\Helper\Logger $loggerHelper
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Wamb\Helper\ConfigData $configDataHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository
     * @param \Riki\Wamb\Model\RegisterRepository $registerRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    public function __construct(
        \Riki\Wamb\Helper\Logger $loggerHelper,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Wamb\Helper\ConfigData $configDataHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository,
        \Riki\Wamb\Model\RegisterRepository $registerRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
    ) {
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configDataHelper = $configDataHelper;
        $this->customerRepository = $customerRepository;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->ambCustomerRepository = $ambCustomerRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->registerRepository = $registerRepository;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->loggerHelper->getCronLogger()->info('Starting ...');
        if (!$this->configDataHelper->getWambCronEnable()) {
            $this->loggerHelper->getCronLogger()->info('Cron is disabled');
            return false;
        }

        $customerIds = $this->registerRepository->createFromArray()
            ->getResource()
            ->getWaitingWambCustomerIds();
        $this->loggerHelper->getCronLogger()->info('Total customers: ' . count($customerIds));
        if (!$customerIds) {
            return false;
        }

        $allowedStatus = [
            StatusInterface::WAITING,
            StatusInterface::ERROR
        ];
        //set params data config when call api
        $dataSubCustomer = $this->getListParamsConfigMapping();
        $paramsSubCustomer = $this->getListParamsForWamb();

        foreach ($customerIds as $consumerDbId => $customerId) {
            $this->loggerHelper->getCronLogger()->info("Registering customer ID [{$customerId}], consumer db ID [{$consumerDbId}]");

            $status = null;

            $query = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId, 'eq')
                ->addFilter('status', $allowedStatus, 'in')
                ->create();
            $registers = $this->registerRepository->getList($query)->getItems();

            /** @var \Riki\Wamb\Model\Register $register */
            foreach ($registers as $register) {
                try {
                    if ($register->getIsRegistered()) {
                        $status = StatusInterface::SUCCESS;
                        $msg = "The customer [$consumerDbId] has WAMB_Status = 1";
                        $register->addHistory(EventInterface::CRON_SET_AMBASSADOR_FAILD, $msg, [
                            'order_id' => $register->getOrderId(),
                            'data_response'=> $register->getConsumerInfo()
                        ]);
                    }

                    if ($register->getIsWambMembership()) {
                        $status = StatusInterface::SUCCESS;
                        $msg = "The customer [$consumerDbId] has Membership Wellness Ambassador";
                        $register->addHistory(EventInterface::CRON_SET_AMBASSADOR_FAILD, $msg, [
                            'order_id' => $register->getOrderId(),
                            'data_response'=> $register->getConsumerInfo()
                        ]);
                    }

                    if ($status == StatusInterface::SUCCESS) {
                        $register->setStatus($status);
                        $this->registerRepository->save($register);
                        continue;
                    }

                    if (!$register->getCanRegister()) {
                        continue;
                    }

                    //check membership ambassador
                    if (isset($paramsSubCustomer['WAMB_HOME_FLG'])) {
                        if ($register->getIsAmbMembership()) {
                            $dataSubCustomer[$paramsSubCustomer['WAMB_HOME_FLG']] = null;
                        }else{
                            $dataSubCustomer[$paramsSubCustomer['WAMB_HOME_FLG']] = 1;
                        }
                    }

                    $response = $this->setCustomerAmbassadorWAMB($customerId, $dataSubCustomer);

                    if (!empty($response) && $response != false) {
                        $status = StatusInterface::SUCCESS;
                        $register->setStatus($status);
                        $statusUpdate = EventInterface::CRON_SET_AMBASSADOR_SUCCESS;
                        $msg = "[$customerId] Set customer ambassador success";
                    }else{
                        $register->setStatus(StatusInterface::ERROR);
                        $statusUpdate = EventInterface::CRON_SET_AMBASSADOR_FAILD;
                        $msg = "[$customerId] Set customer ambassador error";
                    }

                    //update status customer
                    $this->registerRepository->save($register);

                    //save history
                    $register->addHistory($statusUpdate, $msg, [
                        'order_id' => $register->getOrderId(),
                        'rule_id'  => $register->getRuleId(),
                        'detail' => [
                            'data_request' => $dataSubCustomer,
                            'data_response' => $response
                        ]
                    ]);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->loggerHelper->getCronLogger()->warning($e);
                } catch (\Exception $e) {
                    $this->loggerHelper->getCronLogger()->critical($e);
                }
            }
        }

        $this->loggerHelper->getCronLogger()->info('Finish');

        return true;
    }

    /**
     * @param $attribute
     * @return null
     */
    public function getDataCustomerAttribute($attribute)
    {
        $result = null;
        $customer = $this->customerData;

        /* @var \Magento\Customer\Api\CustomerRepositoryInterface $customer */
        $dataAttribute = $customer->getCustomAttribute($attribute);

        if (isset($dataAttribute)) {
            $result = $dataAttribute->getValue();
        }
        return $result;
    }


    /**
     * List params for set customer ambassador WAMB
     *
     * @return array
     */
    public function getListParamsForWamb()
    {
        $arrData = [
            'WAMB_Status' => 1360,
            'WAMB_application_date' => 1361,
            'WAMB_Contract_date' => 1362,
            'WAMB_withdrawal_date' => 1363,
            'WAMB_course_name' => 1364,
            'WAMB_course_code' => 1365,
            'WAMB_Company_name' => 1366,
            'WAMB_Flg' => 1202,
            'AMB_candidate' => 1240,
            'WAMB_HOME_FLG' => 1290,
        ];
        return $arrData;
    }

    /**
     * Set data for customer ambassador WAMB
     *
     * @param array $arrData
     * @return array
     */
    public function prepareSetCustomerForWamb($arrData = [])
    {
        $arrParams = $this->getListParamsForWamb();
        $aSubCustomerData = [];
        foreach ($arrParams as $key => $value) {
            $aSubCustomerData[$value] = null;
            if (isset($arrData[$key]) && $arrData[$key] != null) {
                $aSubCustomerData[$value] = trim($arrData[$key]);
            }
        }
        return $aSubCustomerData;
    }

    /**
     * Set data for customer ambassador wamb
     *
     * @param $customerId
     * @param $dataSubCustomer
     * @return bool
     */
    public function setCustomerAmbassadorWAMB($customerId, $dataSubCustomer)
    {
        $this->customerData = $this->customerRepository->getById($customerId);
        $consumerDbId = $this->getDataCustomerAttribute('consumer_db_id');
        $responseSubCustomer = false;
        if ($consumerDbId != null) {
            $responseSubCustomer = $this->rikiCustomerRepository->setCustomerSubAPI($consumerDbId, $dataSubCustomer);
        }
        return $responseSubCustomer;
    }

    /**
     * Get data mapping value
     *
     * @return array
     */
    public function getListParamsConfigMapping()
    {
        //set params for call api set sub customer
        $timeZone = $this->timezoneInterface->getConfigTimezone();
        $applicationDate = $this->timezoneInterface->date()->setTimezone(new \DateTimeZone($timeZone))->format('Y/m/d');
        $contractDate = $this->timezoneInterface->date()->setTimezone(new \DateTimeZone($timeZone))->format('Y/m/d');
        $params = [
            'WAMB_Status' => 1,
            'WAMB_application_date' => $applicationDate,
            'WAMB_Contract_date' => $contractDate,
            'WAMB_withdrawal_date' => null,
            'WAMB_Company_name' => null,
            'WAMB_Flg' => 1,
            'AMB_candidate' => NULL,
            'WAMB_HOME_FLG' => NULL,
            'WAMB_course_name' => $this->configDataHelper->getWambCourseName(),
            'WAMB_course_code' => $this->configDataHelper->getWambCourseCode(),
        ];

        $result = $this->prepareSetCustomerForWamb($params);
        return $result;
    }
}