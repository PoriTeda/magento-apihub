<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\ResourceModel\Indexer\Mview;

class Shosha
{

    /**
     * @var \Riki\Customer\Api\ShoshaRepositoryInterface
     */
    protected $shoshaRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Shosha constructor.
     * @param \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Riki\Customer\Api\ShoshaRepositoryInterface $shoshaRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->shoshaRepository = $shoshaRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param null $shoshaBusinessIds
     * @return $this
     */
    public function reindexAll($shoshaBusinessIds = null)
    {
        $connection = $this->resourceConnection->getConnection();
        $filterShosha = $this->searchCriteriaBuilder->addFilter('id', $shoshaBusinessIds,'in');
        $aShoshaCustomers = $this->shoshaRepository->getList($filterShosha->create());

        $aShoshaBusinessCode = [];
        $aShoshaMapping = [];
        foreach($aShoshaCustomers->getItems() as $shoshaCustomer){
            if($shoshaCustomer->getData('shosha_business_code')){
                $aShoshaBusinessCode[] = $shoshaCustomer->getData('shosha_business_code');
                $aShoshaMapping[$shoshaCustomer->getData('shosha_business_code')] = $shoshaCustomer ;
            }
        }
        //get shosha business code


        $filterCustomer = $this->searchCriteriaBuilder->addFilter('shosha_business_code', $aShoshaBusinessCode,'in');
        $aCustomer = $this->customerRepository->getList($filterCustomer->create());

        foreach($aCustomer->getItems() as $customer){
            if($customer->getCustomAttribute('shosha_business_code') && $customer->getCustomAttribute('shosha_business_code')->getValue() && isset($aShoshaMapping[$customer->getCustomAttribute('shosha_business_code')->getValue()])){
                $aShoshaInfo = $aShoshaMapping[$customer->getCustomAttribute('shosha_business_code')->getValue()];
                if($connection->tableColumnExists($connection->getTableName('customer_grid_flat'),'shosha_in_charge')){
                    $connection->update($connection->getTableName('customer_grid_flat'),[
                        'shosha_cmp' => $aShoshaInfo->getData('shosha_cmp'),
                        'shosha_cmp_kana' => $aShoshaInfo->getData('shosha_cmp_kana'),
                        'shosha_code' => $aShoshaInfo->getData('shosha_code'),
                        'shosha_dept' => $aShoshaInfo->getData('shosha_dept'),
                        'shosha_dept_kana' => $aShoshaInfo->getData('shosha_dept_kana'),
                        'shosha_first_code' => $aShoshaInfo->getData('shosha_first_code'),
                        'shosha_in_charge' => $aShoshaInfo->getData('shosha_in_charge'),
                        'shosha_in_charge_kana' => $aShoshaInfo->getData('shosha_in_charge_kana')
                    ],'entity_id = '.$customer->getId());
                }
            }
        }

    }

    /**
     * @param $shoshaBusinessId
     * @return $this
     */
    public function reindexRow($shoshaBusinessId)
    {
        $this->reindexAll([$shoshaBusinessId]);
    }
}
