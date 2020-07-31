<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Setup;

use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Abstract setup class for create staging update for entities.
 */
abstract class AbstractStagingSetup
{
    /**
     * Update repository interface.
     *
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;
    
    /**
     * Factory class for @see \Magento\Staging\Api\Data\UpdateInterface.
     *
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param UpdateInterfaceFactory $updateFactory
     */
    public function __construct(UpdateRepositoryInterface $updateRepository, UpdateInterfaceFactory $updateFactory)
    {
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
    }

    /**
     * Create staging update for entity.
     *
     * @param array $entity
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    protected function createUpdateForEntity(array $entity)
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($entity['name']);

        $utcTimeZone = new \DateTimeZone('UTC');

        $fromDate = $entity['from_date'] ? $entity['from_date'] : 'now';
        $date = new \DateTime($fromDate, $utcTimeZone);
        $update->setStartTime($date->format('Y-m-d 00:00:00'));

        $currentDateTime = new \DateTime('now', $utcTimeZone);
        if (strtotime($update->getStartTime()) < $currentDateTime->getTimestamp()) {
            $currentDateTime->modify('+1 minutes');
            $update->setStartTime($currentDateTime->format('Y-m-d H:i:s'));
        }

        if ($entity['to_date']) {
            $date = new \DateTime($entity['to_date'], $utcTimeZone);
            $update->setEndTime($date->format('Y-m-d 23:59:59'));
        }

        $isCampaign = isset($entity['is_campaign']) ? (bool)$entity['is_campaign'] : false;
        $update->setIsCampaign($isCampaign);
        
        $this->updateRepository->save($update);
        
        return $update;
    }
}
