<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\Entity\RepositoryFactory;
use Magento\Framework\Registry;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager\Proxy as VersionManagerProxy;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;

/**
 * Class SynchronizeEntityPeriod
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SynchronizeEntityPeriod
{
    /**
     * @var RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * @var StagingList
     */
    protected $stagingList;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var VersionManagerProxy
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EntityStaging
     */
    private $entityStaging;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * SynchronizeEntityPeriod constructor.
     *
     * @param RepositoryFactory $repositoryFactory
     * @param StagingList $stagingList
     * @param MetadataPool $metadataPool
     * @param VersionManagerProxy $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Registry $registry
     * @param EntityStaging $entityStaging
     * @param RetrieverPool $retrieverPool
     * @param CampaignValidator $campaignValidator
     * @param TypeResolver|null $typeResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RepositoryFactory $repositoryFactory,
        StagingList $stagingList,
        MetadataPool $metadataPool,
        VersionManagerProxy $versionManager,
        UpdateRepositoryInterface $updateRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Registry $registry,
        EntityStaging $entityStaging,
        RetrieverPool $retrieverPool,
        CampaignValidator $campaignValidator,
        TypeResolver $typeResolver = null
    ) {
        $this->repositoryFactory = $repositoryFactory;
        $this->stagingList = $stagingList;
        $this->metadataPool = $metadataPool;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->registry = $registry;
        $this->entityStaging = $entityStaging;
        $this->retrieverPool = $retrieverPool;
        $this->campaignValidator = $campaignValidator;
        $this->typeResolver = $typeResolver ?? ObjectManager::getInstance()->get(TypeResolver::class);
    }

    /**
     * Synchronise Entity
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        $this->filterBuilder->setField('moved_to');
        $this->filterBuilder->setConditionType('notnull');

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->create()
            ]
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->updateRepository->getList($searchCriteria);
        $initVersion = $this->versionManager->getVersion()->getId();
        foreach ($searchResults->getItems() as $update) {
            foreach ($this->stagingList->getEntityTypes() as $entityType) {
                $this->synchronizeEntity(
                    $entityType,
                    $update->getId(),
                    $update->getMovedTo()
                );
            }
            $this->updateRepository->delete($update);
        }
        $this->versionManager->setCurrentVersionId($initVersion);

        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }

    /**
     * @param string $entityType
     * @param int $oldVersionId
     * @param int $newVersionId
     * @return void
     * @throws LocalizedException
     */
    private function synchronizeEntity($entityType, $oldVersionId, $newVersionId)
    {
        if ($oldVersionId == $newVersionId) {
            return;
        }
        $entityList = $this->getVersions($entityType, $oldVersionId);
        if (!$entityList) {
            return;
        }
        $arguments['origin_in'] = $oldVersionId;
        $retriever = $this->retrieverPool->getRetriever($entityType);
        foreach ($entityList as $entityId) {
            $this->versionManager->setCurrentVersionId($oldVersionId);
            $entity = $retriever->getEntity($entityId);
            $realEntityType = $this->typeResolver->resolve($entity);
            if ($realEntityType !== $entityType) {
                throw new LocalizedException(__('Repository should return instance of %s'));
            }
            if (!$this->campaignValidator->canBeScheduled($entity, $newVersionId, $oldVersionId)) {
                throw new LocalizedException(__('Can not be rescheduled'));
            }
            $this->versionManager->setCurrentVersionId($newVersionId);
        }
        foreach ($entityList as $entityId) {
            $this->versionManager->setCurrentVersionId($oldVersionId);
            $entity = $retriever->getEntity($entityId);
            $this->versionManager->setCurrentVersionId($newVersionId);
            $this->entityStaging->schedule($entity, $newVersionId, $arguments);
        }
    }

    /**
     * Get all entities assigned to update ($versionId)
     *
     * @param string $entityType
     * @param int $versionId
     * @return array
     */
    private function getVersions($entityType, $versionId)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $select = $metadata->getEntityConnection()->select()
            ->reset()
            ->from(
                ['table_name' => $metadata->getEntityTable()],
                [$metadata->getIdentifierField()]
            )
            ->where('created_in = ?', $versionId)
            ->setPart('disable_staging_preview', true);
        return $metadata->getEntityConnection()->fetchCol($select);
    }
}
