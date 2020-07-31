<?php

namespace Riki\SubscriptionMachine\Observer;

class CheckoutSubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{
    const CONFIG_FREE_MACHINE_EMAIL_ENABLE = 'freemachine/outofstock/enable';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\Subscription\Helper\Order\Data
     */
    protected $helperOrderData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Subscription\Logger\LoggerFreeMachine
     */
    protected $loggerFreeMachine;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var array: used to update to KSS when order applied a machine product
     */
    protected $_mappingMachineCustomerSub = [
        'NBA' => [
            '1530' => 'AMB_NBA_SKU',
            '1540' => 'AMB_NBA_NAME',
            '1550' => 'AMB_NBA_DATE',
        ],
        'NDG' => [
            '1531' => 'AMB_NDG_SKU',
            '1541' => 'AMB_NDG_NAME',
            '1551' => 'AMB_NDG_DATE',
        ],
        'SPT' => [
            '1532' => 'AMB_SPT_SKU',
            '1542' => 'AMB_SPT_NAME',
            '1552' => 'AMB_SPT_DATE',
        ],
        'BLC' => [
            '1533' => 'AMB_ICS_SKU',
            '1543' => 'AMB_ICS_NAME',
            '1553' => 'AMB_ICS_DATE',
        ], /* Machine type code ICS */

        'Nespresso' => [
            '1534' => 'AMB_NSP_SKU',
            '1544' => 'AMB_NSP_NAME',
            '1554' => 'AMB_NSP_DATE',
        ], /* Machine type code NSP */

        'DUO' => [
            '2571' => 'AMB_DUO_SKU',
            '2620' => 'AMB_DUO_NAME',
            '2621' => 'AMB_DUO_DATE',
        ]
    ];

    /**
     * CheckoutSubmitAllAfter constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Order\Data $helperOrderData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Data $helperOrderData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->_registry = $registry;
        $this->helperOrderData = $helperOrderData;
        $this->scopeConfig = $scopeConfig;
        $this->loggerFreeMachine = $loggerFreeMachine;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->localeDate = $timezone;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /*update customer machine status - normal order*/
        $this->updateCustomerMachineStatusForOrder($order);

        /*update customer machine status - free machine order*/
        $this->updateCustomerMachineStatusForFreeMachineOrder($order);
    }

    /**
     * update customer machine status for normal order which is included freemachine
     *
     * @param $order
     */
    private function updateCustomerMachineStatusForOrder($order)
    {
        if ($order->getData('free_machine_order')) {
            return;
        }

        /**
         * free machine data has been added to normal order
         *      normal order is a order which contain normal product and free machine item both
         */
        $freeMachineAddedToCart = $this->_registry->registry('free_machine_added_to_cart');

        if ($freeMachineAddedToCart) {
            $this->updateCustomerMachineStatus($freeMachineAddedToCart, $order);
            $this->_registry->unregister('free_machine_added_to_cart');
        }
    }

    /**
     * update customer machine status for free machine order
     *
     * @param $order
     */
    private function updateCustomerMachineStatusForFreeMachineOrder($order)
    {
        if (!$order->getData('free_machine_order')) {
            return;
        }
        /**
         * free machine data has been added to free machine order
         *      free machine order is a order which contain only free machine item
         */
        $freeMachineAddedToFreeMachineCart = $this->_registry->registry(
            'free_machine_added_to_free_machine_cart'
        );

        if ($freeMachineAddedToFreeMachineCart) {
            $this->updateCustomerMachineStatus($freeMachineAddedToFreeMachineCart, $order);
            $this->_registry->unregister('free_machine_added_to_free_machine_cart');
        }
    }

    /**
     * Update customer machine status after place order success
     *
     * @param $machineData
     * @param $order
     */
    private function updateCustomerMachineStatus($machineData, $order)
    {
        if (empty($machineData)) {
            return;
        }

        $profileId = $order->getData('subscription_profile_id');

        foreach ($machineData as $machineTypeCode => $machine) {
            if (isset($machine['status'])) {
                if ($machine['status'] == 1) {
                    try {
                        $this->updateForConsumer($profileId, $machine);
                    } catch (\Exception $e) {
                        $this->helperOrderData->getLogger()->critical($e);
                    }
                } else {
                    $this->updateForConsumer($profileId, $machine);

                    if ($this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_ENABLE)) {
                        if ($machine['type'] == 'oos') {
                            try {
                                $this->helperOrderData->sendEmailNotifyToAdmin(
                                    $machine['consumer_db_id'],
                                    $machine['variables'],
                                    $machine['type']
                                );
                            } catch (\Exception $e) {
                                $this->helperOrderData->getLogger()->critical($e);
                            }
                        } else {
                            try {
                                $this->helperOrderData->sendEmailNotifyToAdmin(
                                    $machine['consumer_db_id'],
                                    $machineTypeCode,
                                    $machine['type']
                                );
                            } catch (\Exception $e) {
                                $this->helperOrderData->getLogger()->critical($e);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Update status of machine_customer
     *
     * @param $profileId
     * @param $machine
     */
    public function updateForConsumer($profileId, $machine)
    {
        $machineCustomer = $machine['machine'];
        $statusId = $machine['status'];
        $consumerDbId = $machine['consumer_db_id'];
        $sku = isset($machine['sku'])?$machine['sku']:null;
        $machineTypeCode = $machineCustomer->getData('machine_type_code');
        $arrSubProfileId = $this->rikiCustomerRepository->getSubProfileIdByMachineTypeOptionArray();
        $machineTypeSubProfileId = $arrSubProfileId[$machineTypeCode];
        $machineCustomerSubMapping = $this->_mappingMachineCustomerSub;
        $dataToUpdate = [
            $machineTypeSubProfileId => $statusId
        ];
        if ($statusId == 1) {
            $name = isset($machine['name']) ? $machine['name'] : null;
            $allInfoNeedToUpdate = isset($machineCustomerSubMapping[$machineTypeCode])
                ? $machineCustomerSubMapping[$machineTypeCode]
                : [];
            foreach ($allInfoNeedToUpdate as $key => $title) {
                if (strpos($title, 'SKU') !== false) {
                    $dataToUpdate[$key] = $sku;
                } elseif (strpos($title, 'NAME') !== false) {
                    $dataToUpdate[$key] = $name;
                } else {
                    $dataToUpdate[$key] = $this->localeDate->date()->format('Y-m-d H:i:s');
                }
            }
        }

        $responseSubCustomer = $this->rikiCustomerRepository->setCustomerSubAPI($consumerDbId, $dataToUpdate);
        if ($responseSubCustomer) {
            $machineCustomer->setData('status', $statusId);
            if ($sku != null) {
                $machineCustomer->setData('sku', $sku);
            }
            try {
                $machineCustomer->save();
                $this->loggerFreeMachine->addInfo(
                    'ProfileID ' . $profileId . '::Customer #' . $consumerDbId . '::' . $machineTypeCode .
                    '::Status changed to ' . $statusId
                );
            } catch (\Exception $e) {
                $this->loggerFreeMachine->addError(
                    'ProfileID '.$profileId.'::Customer #' . $consumerDbId . '::' . $machineTypeCode .
                    ' failed to update status to ' . $statusId
                );
                $this->loggerFreeMachine->addCritical($e);
            }
        }
    }
}
