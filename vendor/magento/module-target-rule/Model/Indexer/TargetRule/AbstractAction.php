<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule;

use Magento\Framework\Stdlib\DateTime;

/**
 * Abstract action reindex class
 *
 * @package Magento\TargetRule\Model\Indexer\TargetRule
 */
abstract class AbstractAction
{
    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_ruleCollectionFactory;

    /**
     * @var \Magento\TargetRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Resource model instance
     *
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $_resource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var array
     */
    protected $_isIndexCleaned = [];

    /**
     * @param \Magento\TargetRule\Model\RuleFactory $ruleFactory
     * @param \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\TargetRule\Model\ResourceModel\Index $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\TargetRule\Model\RuleFactory $ruleFactory,
        \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\TargetRule\Model\ResourceModel\Index $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->_ruleFactory = $ruleFactory;
        $this->_resource = $resource;
        $this->_ruleCollectionFactory = $ruleCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
    }

    /**
     * Execute action for given ids
     *
     * @param array|int $ids
     *
     * @return void
     */
    abstract public function execute($ids);

    /**
     * Refresh entities index
     *
     * @param array $productIds
     * @return array Affected ids
     */
    protected function _reindexByProductIds($productIds = [])
    {
        $indexResource = $this->_resource;

        // remove old cache index data
        $this->_cleanIndex();

        // remove old matched product index
        $indexResource->removeProductIndex($productIds);

        $ruleCollection = $this->_ruleCollectionFactory->create();

        // We only need to update each rule affected by this product once.
        foreach ($ruleCollection as $rule) {
            /** @var $rule \Magento\TargetRule\Model\Rule */
            if ($this->_validateRuleByEntityIds($rule, $productIds)) {
                $matchedProductIds = $rule->getMatchingProductIds();
                $rule->getResource()->bindRuleToEntity($rule->getId(), $matchedProductIds, 'product');
                $rule->getResource()->cleanCachedDataByProductIds(
                    array_unique(
                        array_merge(
                            $productIds,
                            $matchedProductIds
                        )
                    )
                );
            }
        }
        return $this;
    }

    /**
     * Check if any product in a list matches rule conditions
     *
     * @param \Magento\TargetRule\Model\Rule $rule
     * @param array $productIds
     * @return bool True if any match
     */
    private function _validateRuleByEntityIds($rule, $productIds)
    {
        foreach ($productIds as $productId) {
            if ($rule->validateByEntityId($productId)) {
                // Eagerly return: no need to waste time checking the rest.
                return true;
            }
        }

        return false;
    }

    /**
     * Reindex all
     *
     * @return void
     */
    protected function _reindexAll()
    {
        $indexResource = $this->_resource;

        // remove old cache index data
        $this->_cleanIndex();
        $indexResource->removeProductIndex([]);

        $ruleCollection = $this->_ruleCollectionFactory->create();

        foreach ($ruleCollection as $rule) {
            $indexResource->saveProductIndex($rule);
        }
    }

    /**
     * Clean all
     *
     * @return void
     */
    protected function _cleanAll()
    {
        $websites = $this->_storeManager->getWebsites();
        foreach ($websites as $website) {
            /* @var $website \Magento\Store\Model\Website */
            $store = $website->getDefaultStore();
            $date = $this->_localeDate->scopeDate($store);
            if ($date->diff(new \DateTime('1970-01-01 00:00:00'))->h === 0) {
                $storeIds = $website->getStoreIds();
                $this->_cleanIndex(null, $storeIds);
            }
        }
    }

    /**
     * Reindex targetrules by product id
     *
     * @param int|null $productId
     * @return $this
     */
    protected function _reindexByProductId($productId = null)
    {
        return $this->_reindexByProductIds(null === $productId ? [] : [$productId]);
    }

    /**
     * Refresh entities index by rule Ids
     *
     * @param array $ruleIds
     * @return array Affected ids
     */
    protected function _reindexByRuleIds($ruleIds = [])
    {
        foreach ($ruleIds as $ruleId) {
            $this->_reindexByRuleId($ruleId);
        }
    }

    /**
     * Reindex rule by ID
     *
     * @param int $ruleId
     * @return void
     */
    protected function _reindexByRuleId($ruleId)
    {
        /** @var \Magento\TargetRule\Model\Rule $rule */
        $rule = $this->_ruleFactory->create();
        $rule->load($ruleId);
        // remove old cache index data
        $this->_cleanIndex();
        /** @var \Magento\TargetRule\Model\ResourceModel\Rule $ruleResource */
        $ruleResource = $rule->getResource();
        $productIdsBeforeUnbind = $ruleResource->getAssociatedEntityIds($ruleId, 'product');
        $ruleResource->unbindRuleFromEntity($ruleId, [], 'product');
        if ($rule->getId()) {
            $matchedProductIds = $rule->getMatchingProductIds();
        } else {
            $matchedProductIds = [];
        }
        $ruleResource->bindRuleToEntity($ruleId, $matchedProductIds, 'product');
        $ruleResource->cleanCachedDataByProductIds(
            array_unique(
                array_merge(
                    $productIdsBeforeUnbind,
                    $matchedProductIds
                )
            )
        );
    }

    /**
     * Remove targetrule's index
     *
     * @param int|null $typeId
     * @param \Magento\Store\Model\Store|int|array|null $store
     * @return $this
     */
    protected function _cleanIndex($typeId = null, $store = null)
    {
        if (!$this->_isIndexCleaned($typeId, $store)) {
            $this->_resource->cleanIndex($typeId, $store);
        }
        return $this;
    }

    /**
     * Remove products from index
     *
     * @param int|null $productId
     * @return $this
     */
    protected function _deleteProductFromIndex($productId = null)
    {
        $this->_resource->deleteProductFromIndex($productId);

        return $this;
    }

    /**
     * Is index cleaned
     *
     * @param null|int $typeId
     * @param null|int $store
     * @return bool
     */
    protected function _isIndexCleaned($typeId = null, $store = null)
    {
        return isset($this->_isIndexCleaned[$typeId][$store]) ?  $this->_isIndexCleaned[$typeId][$store] : false;
    }

    /**
     * Set index cleaned flag
     *
     * @param null|int $typeId
     * @param null|int $store
     * @param bool $flag
     * @return $this
     */
    protected function _setIndexCleaned($typeId = null, $store = null, $flag = true)
    {
        $this->_isIndexCleaned[$typeId][$store] = $flag;
        return $this;
    }
}
