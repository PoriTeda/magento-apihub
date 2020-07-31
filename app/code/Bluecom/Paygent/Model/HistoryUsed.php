<?php

namespace Bluecom\Paygent\Model;

class HistoryUsed
{
    /**
     * @var \Bluecom\Paygent\Model\PaygentHistory
     */
    protected $paygentHistory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
     */
    protected $customerResourceFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Bluecom\Paygent\Model\PaygentHistory $paygentHistory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->paygentHistory = $paygentHistory;
        $this->customerFactory = $customerFactory;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->logger = $logger;
    }

    /**
     * Save paygent history.
     *
     * @param array $data
     * @return boolean
     */
    public function savePaygentHistory($data)
    {
        if (isset($data['order_number']) && $data['order_number'] != null) {
            $expire = date('Y-m-d', strtotime($data['used_date']));
            try {
                $customer = $this->customerFactory->create();
                $customerData = $customer->getDataModel();
                $customerData->setId($data['customer_id']);
                $customerData->setCustomAttribute('paygent_transaction_id', $data['trading_id']);
                $customerData->setCustomAttribute('paygent_transaction_expire', $expire);
                $customer->updateData($customerData);
                $customerResource = $this->customerResourceFactory->create();
                $customerResource->saveAttribute($customer, 'paygent_transaction_id');
                $customerResource->saveAttribute($customer, 'paygent_transaction_expire');
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        try {
            $this->paygentHistory->setData($data)->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return true;
    }
}
