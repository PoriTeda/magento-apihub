<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

class GroupRepository
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    private $appConfig;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface
     */
    private $updateIndex;

    /**
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogPermissions\App\ConfigInterface $appConfig
     * @param \Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface $updateIndex
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogPermissions\App\ConfigInterface $appConfig,
        \Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface $updateIndex
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->appConfig = $appConfig;
        $this->updateIndex = $updateIndex;
    }

    /**
     * Invalidate indexer on customer group save
     *
     * @param \Magento\Customer\Api\GroupRepositoryInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Customer\Api\Data\GroupInterface $customerGroup
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        \Magento\Customer\Api\GroupRepositoryInterface $subject,
        \Closure $proceed,
        \Magento\Customer\Api\Data\GroupInterface $customerGroup
    ) {
        $needInvalidating = !$customerGroup->getId();

        $customerGroup = $proceed($customerGroup);

        if ($needInvalidating && $this->appConfig->isEnabled()) {
            $this->updateIndex->update($customerGroup, $needInvalidating);
        }

        return $customerGroup;
    }

    /**
     * Invalidate indexer on customer group delete
     *
     * @param \Magento\Customer\Api\GroupRepositoryInterface $subject
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(\Magento\Customer\Api\GroupRepositoryInterface $subject)
    {
        return $this->invalidateIndexer();
    }

    /**
     * Invalidate indexer on customer group delete
     *
     * @param \Magento\Customer\Api\GroupRepositoryInterface $subject
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteById(\Magento\Customer\Api\GroupRepositoryInterface $subject)
    {
        return $this->invalidateIndexer();
    }

    /**
     * Invalidate indexer
     *
     * @return bool
     */
    protected function invalidateIndexer()
    {
        if ($this->appConfig->isEnabled()) {
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)->invalidate();
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)->invalidate();
        }
        return true;
    }
}
