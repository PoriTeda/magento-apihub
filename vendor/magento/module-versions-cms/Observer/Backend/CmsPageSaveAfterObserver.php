<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node as HierarchyNode;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node;

class CmsPageSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Hierarchy
     */
    protected $cmsHierarchy;

    /**
     * @var HierarchyNode
     */
    protected $hierarchyNode;

    /**
     * @var Node
     */
    protected $hierarchyNodeResource;

    /**
     * @param Hierarchy $cmsHierarchy
     * @param HierarchyNode $hierarchyNode
     * @param Node $hierarchyNodeResource
     */
    public function __construct(
        Hierarchy $cmsHierarchy,
        HierarchyNode $hierarchyNode,
        Node $hierarchyNodeResource
    ) {
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNode = $hierarchyNode;
        $this->hierarchyNodeResource = $hierarchyNodeResource;
    }

    /**
     * Process extra data after cms page saved
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var Page $page */
        $page = $observer->getEvent()->getObject();

        if (!$this->cmsHierarchy->isEnabled()) {
            return $this;
        }

        // Rebuild URL rewrites if page has changed for identifier
        if ($page->dataHasChangedFor('identifier')) {
            $this->hierarchyNode->updateRewriteUrls($page);
        }

        /**
         * Append page to selected nodes it will remove pages from other nodes
         * which are not specified in array. So should be called even array is empty!
         * Returns array of new ids for page nodes array( oldId => newId ).
         */
        $this->hierarchyNode->appendPageToNodes($page, $page->getAppendToNodes());

        /**
         * Update sort order for nodes in parent nodes which have current page as child
         */
        foreach ($page->getNodesSortOrder() as $nodeId => $value) {
            $this->hierarchyNodeResource->updateSortOrder($nodeId, $value);
        }

        return $this;
    }
}
