<?php

namespace Riki\Customer\Model\Repository;

class ShoshaRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Customer\Api\ShoshaRepositoryInterface
{
    /**
     * ShoshaRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Customer\Model\ShoshaFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Customer\Model\ShoshaFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Customer\Api\Data\ShoshaInterface $entity
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function save(\Riki\Customer\Api\Data\ShoshaInterface $entity)
    {
        return parent::executeSave($entity);
    }
}