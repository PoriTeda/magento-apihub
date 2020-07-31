<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CustomerSyncConsumerDbFailure implements ObserverInterface
{

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var \Magento\Framework\Api\FilterBuilder */
    protected $filterBuilder;

    /** @var \Magento\Customer\Model\Config\Share */
    protected $configShare;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * CustomerSyncConsumerDbFailure constructor.
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configShare = $configShare;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
    }

    /**
     * Set other customer email type to Dummy if email is same
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getEvent()->getCustomer();

        $filterBuilder = [
            $this->filterBuilder
                ->setField('email')
                ->setValue($customer->getEmail())
                ->create()
        ];

        if ($this->configShare->isWebsiteScope()) {
            $filterBuilder[] = $this->filterBuilder
                ->setField('website_id')
                ->setValue(intval($customer->getWebsiteId()))
                ->create();
        }
        if ($customer->getId()) {
            $this->filterBuilder
                ->setField('entity_id')
                ->setValue($customer->getId())
                ->setConditionType('neq')
                ->create();
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filterBuilder);

        try {
            $duplicateCustomers = $this->customerRepository->getList($searchCriteria->create())->getItems();

            foreach ($duplicateCustomers as $duplicateCustomer) {
                $codeAttribute = $duplicateCustomer->getCustomAttribute('consumer_db_id');

                if ($codeAttribute && $consumerDbId = $codeAttribute->getValue()) {
                    $consumerDbId = $codeAttribute->getValue();

                    $duplicateCustomer->setCustomAttribute('email_1_type', \Riki\Customer\Model\EmailType::CODE_9);

                    $duplicateCustomer->setEmail($consumerDbId . '@dm.jp');

                    // NED-4865 remove dummy account's consumer db id data to avoid infinite loop
                    if ($customer->getCustomAttribute('consumer_db_id') == $consumerDbId) {
                        $duplicateCustomer->setCustomAttribute('consumer_db_id', null);
                    }
                    $this->customerRepository->save($duplicateCustomer);
                } else {
                    throw new LocalizedException(__('Consumer DB ID is empty'));
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('Something went wrong saving the customer attribute.'));
        }
    }
}