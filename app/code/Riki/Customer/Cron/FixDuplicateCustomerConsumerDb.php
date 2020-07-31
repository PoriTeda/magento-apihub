<?php

namespace Riki\Customer\Cron;

class FixDuplicateCustomerConsumerDb
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $addressHelper;

    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface[]
     */
    protected $addressArray = [];

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory
    ) {
        $this->_log = $logger;
        $this->customerFactory = $customerFactory;
        $this->resource = $resource;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileFactory = $profileFactory;
        $this->addressHelper = $addressHelper;
        $this->customerAddressFactory = $customerAddressFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $defaultConnection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $table = "customer_entity";
        // Get consumer_db_id that assign to more than 1 customer
        $sqlGetDuplicates = 'SELECT `consumer_db_id`'
            . ' FROM `' . $table . '`'
            . ' WHERE `consumer_db_id` IS NOT NULL'
            . ' GROUP BY `consumer_db_id`'
            . ' HAVING COUNT(entity_id) > 1';

        $duplicateList = $defaultConnection->fetchAll($sqlGetDuplicates);
        foreach ($duplicateList as $duplicate) {
            $customers = $this->customerFactory->create()->getCollection()
                ->addFieldToFilter('consumer_db_id', ['eq' => $duplicate['consumer_db_id']]);

            // find base customer data to migrate to
            $baseCustomer = null;
            $dummyCustomerId = [];
            foreach ($customers as $customer) {
                $emailData = explode('@', $customer->getEmail());
                if (is_numeric($emailData[0]) && $emailData[0] == $customer->getConsumerDbId()) {
                    if ($emailData[0] == $customer->getConsumerDbId()) {
                        $dummyCustomerId[] = $customer->getId();
                    }
                } else {
                    $baseCustomer = $customer;
                }
            }

            // Process migrate order
            $criteria = $this->searchCriteriaBuilder->addFilter('customer_id', $dummyCustomerId, 'in')
                ->create();
            $orders = $this->orderRepository->getList($criteria);
            if ($orders->getTotalCount()) {
                foreach ($orders->getItems() as $order) {
                    $order->setCustomerId($baseCustomer->getId());
                    $order->setCustomerEmail($baseCustomer->getEmail());
                    $order->save();
                }
            }

            // Process migrate profile
            $profileColection = $this->profileFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', ['in' => $dummyCustomerId])
                ->setFlag('original', 1)
                ->load();
            if ($profileColection->getSize()) {
                // find duplicated address mapping
                foreach ($profileColection as $profile) {
                    $profile->setCustomerId($baseCustomer->getId());

                    $profileProductCart = $profile->getProductCart();
                    $profileAddresses = [];
                    foreach ($profileProductCart as $productCart) {
                        $profileAddresses = [
                            'shipping' => $productCart->getShippingAddressId(),
                            'billing' => $productCart->getBillingAddressId()
                        ];
                        break;
                    }

                    $mappedAddress = [];
                    foreach ($profileAddresses as $type => $addressId) {
                        $profileAddress = $this->getAddressModel($addressId);

                        foreach ($baseCustomer->getAddresses() as $address) {
                            if ($this->addressHelper->compareAddresses($address, $profileAddress)) {
                                $mappedAddress[$type] = $address->getId();
                                break;
                            }
                        }
                    }

                    if (count($mappedAddress) < 2) {
                        $this->_log->debug("NED-5385: Profile {$profile->getId()} of dummy customer does not have any mapped address");
                        continue;
                    }

                    // Process migrate profile_product_cart
                    $this->updateProductCartInformation($profileColection->getConnection(), $profile->getId(), $mappedAddress);
                    $profile->save();
                }
            }

            // Process set consumerDB data to null
            $this->removeConsumerDbIdFromDummyCustomer($defaultConnection, $dummyCustomerId);
        }

    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param $profileId
     * @param $mappedAddress
     */
    private function updateProductCartInformation($connection, $profileId, $mappedAddress)
    {
        $where = ['profile_id = ?' => $profileId];

        $connection->beginTransaction();
        try {
            $connection->update($connection->getTableName('subscription_profile_product_cart'), [
                'billing_address_id' => $mappedAddress['billing'],
                'shipping_address_id' => $mappedAddress['shipping']
            ], $where);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->_log->debug('NED-5385: Error when update profile of duplicate customer');
            $this->_log->debug($e->getMessage());
            $this->_log->debug($e->getTraceAsString());
        }
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param $dummyCustomerId
     */
    private function removeConsumerDbIdFromDummyCustomer($connection, $dummyCustomerId)
    {
        $where = ['entity_id IN (?)' => implode(',', $dummyCustomerId)];

        $connection->beginTransaction();
        try {
            $connection->update($connection->getTableName('customer_entity'), [
                'consumer_db_id' => new \Zend_Db_Expr('NULL')
            ], $where);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->_log->debug('NED-5385: Error when remove consumer_db_id from dummy customer');
            $this->_log->debug($e->getMessage());
            $this->_log->debug($e->getTraceAsString());
        }
    }

    private function getAddressModel($addressId)
    {
        if (!isset($this->addressArray[$addressId])) {
            $this->addressArray[$addressId] = $this->customerAddressFactory->create()->load($addressId);
        }

        return $this->addressArray[$addressId];
    }
}