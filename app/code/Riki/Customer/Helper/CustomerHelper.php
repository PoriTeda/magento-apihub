<?php
namespace Riki\Customer\Helper;

class CustomerHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $_customerRepository;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * CustomerHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
    ) {
        parent::__construct($context);
        $this->_customerRepository = $customerRepository;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
    }

    /**
     * Get customer data by customer id
     *
     * @param $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerDataById($customerId)
    {
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Get customer data  - consumer_db_id
     *
     * @param $customerId
     * @return bool|void
     */
    public function getConsumerIdByCustomerId($customerId)
    {
        $customer = $this->getCustomerDataById($customerId);

        if ($customer) {
            return $this->getCustomerAttribute($customer, 'consumer_db_id');
        }

        return false;
    }

    /**
     * Get customer custom attribute data
     *
     * @param $customer
     * @param $attribute
     * @return bool
     */
    public function getCustomerAttribute($customer, $attribute)
    {
        if ($customer && !empty($customer->getCustomAttribute($attribute))) {
            return $customer->getCustomAttribute($attribute)->getValue();
        }

        return false;
    }

    /**
     * Check and update home address, company address when consumer changed information from KSS
     *
     * @param $customerId
     *
     * @return bool
     */
    public function checkUpdateHomeAndCompanyAddressForCustomer($customerId)
    {
        $customer = $this->getCustomerDataById($customerId);

        if ($customer) {
            $consumerDbId = $this->getCustomerAttribute($customer, 'consumer_db_id');
            if ($consumerDbId) {
                try {
                    $customerApiData = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerDbId);

                    if (isset($customerApiData['customer_api'])) {
                        $customerReturn = $this->rikiCustomerRepository->createUpdateEcCustomer(
                            $customerApiData,
                            $consumerDbId,
                            null,
                            $customer
                        );

                        return $customerReturn;
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e->getMessage());
                }
            }
        }

        return false;
    }
}