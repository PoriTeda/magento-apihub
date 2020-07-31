<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Operation;

use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Framework\EntityManager\Operation\CreateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\Operation\Update;
use Magento\Staging\Model\Operation\Create as StagingCreateOperation;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create implements CreateInterface
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var Update
     */
    protected $operationUpdate;

    /**
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @var StagingCreateOperation
     */
    private $operationCreate;

    /**
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param Update $operationUpdate
     * @param UpdateInterfaceFactory $updateFactory
     * @param StagingCreateOperation $operationCreate
     */
    public function __construct(
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository,
        Update $operationUpdate,
        UpdateInterfaceFactory $updateFactory,
        StagingCreateOperation $operationCreate
    ) {
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->operationUpdate = $operationUpdate;
        $this->updateFactory = $updateFactory;
        $this->operationCreate = $operationCreate;
    }

    /**
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        // Create update from now to max_int
        $updateVersionId = $this->getUpdateVersion($entity);
        $entity = $this->operationCreate->execute($entity, $arguments);
        // Create staging update for default entity
        $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->versionManager->setCurrentVersionId($updateVersionId);
        $this->operationUpdate->execute($entity, array_merge($arguments, ['created_in' => $updateVersionId]));
        $this->versionManager->setCurrentVersionId($currentVersionId);
        return $entity;
    }

    /**
     * Get appropriate update version for the update.
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $entity
     * @return int
     */
    private function getUpdateVersion($entity)
    {
        $updateId = $this->createUpdate($entity);
        $currentVersion = $this->versionManager->getVersion()->getId();
        if ($updateId == $currentVersion) {
            return $this->updateRepository->get($updateId)->getId();
        }
        return $updateId;
    }

    /**
     * Create update for entity dates.
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $entity
     * @return int
     */
    private function createUpdate($entity)
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($entity->getName());
        $update->setIsCampaign(false);
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $update->setStartTime($date->format('Y-m-d H:i:s'));
        $this->updateRepository->save($update);
        return $update->getId();
    }
}
