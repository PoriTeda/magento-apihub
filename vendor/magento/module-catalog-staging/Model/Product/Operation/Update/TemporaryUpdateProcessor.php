<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Operation\Update;

use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Entity\Builder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Api\Data\ProductInterface;

class TemporaryUpdateProcessor implements \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersion;

    /**
     * @var CreateEntityVersion
     */
    private $createEntityVersion;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ProductLinkRepositoryInterface
     */
    private $linkRepository;

    /**
     * @param EntityManager $entityManager
     * @param VersionManager $versionManager
     * @param ReadEntityVersion $entityVersion
     * @param CreateEntityVersion $createEntityVersion
     * @param Builder $builder
     * @param Helper $initializationHelper
     * @param ProductLinkRepositoryInterface $linkRepository
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        EntityManager $entityManager,
        VersionManager $versionManager,
        ReadEntityVersion $entityVersion,
        CreateEntityVersion $createEntityVersion,
        Builder $builder,
        Helper $initializationHelper,
        ProductLinkRepositoryInterface $linkRepository
    ) {
        $this->entityManager = $entityManager;
        $this->versionManager = $versionManager;
        $this->entityVersion = $entityVersion;
        $this->createEntityVersion = $createEntityVersion;
        $this->builder = $builder;
        $this->linkRepository = $linkRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity, $versionId, $rollbackId = null)
    {
        $previousVersionId = $this->entityVersion->getPreviousVersionId(
            ProductInterface::class,
            $versionId,
            $entity->getId()
        );
        $nextVersionId = $this->entityVersion->getNextVersionId(ProductInterface::class, $rollbackId, $entity->getId());
        $this->versionManager->setCurrentVersionId($previousVersionId);

        /** @var \Magento\Catalog\Model\Product $previousEntity */
        $previousEntity = clone $entity;
        $previousEntity->unsetData();
        $previousEntity->setId($entity->getId());
        $this->loadEntity($previousEntity);

        $this->versionManager->setCurrentVersionId($rollbackId);

        $this->buildEntity($previousEntity);
        $arguments = [
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
            'origin_in' => $previousVersionId
        ];
        $this->createEntityVersion->execute($previousEntity, $arguments);

        foreach ($previousEntity->getProductLinks() as $link) {
            $this->linkRepository->save($link);
        }

        $this->versionManager->setCurrentVersionId($versionId);
        return $entity;
    }

    /**
     * @param object $entity
     * @return void
     */
    public function loadEntity($entity)
    {
        $entity->setProductLinks();
        $this->entityManager->load($entity, $entity->getId());
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function buildEntity($entity)
    {
        $entity = $this->builder->build($entity);
        $entity->setRowId(null);
    }
}
