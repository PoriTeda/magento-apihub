<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;

class CmsPageSaveBeforeObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @param Data $jsonHelper
     */
    public function __construct(
        Data $jsonHelper
    ) {
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Prepare cms page object before it will be saved
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var Page $page */
        $page = $observer->getEvent()->getObject();

        if (!$page->getId()) {
            // Newly created page should be auto assigned to website root
            $page->setWebsiteRoot(true);
        }

        $nodesData = $this->getNodesOrder($page->getNodesData());

        $page->setNodesSortOrder($nodesData['sortOrder']);
        $page->setAppendToNodes($nodesData['appendToNodes']);
        return $this;
    }

    /**
     * Check nodes data and return new sort order for nodes
     *
     * @param string $nodesData
     * @return array
     */
    protected function getNodesOrder($nodesData)
    {
        $appendToNodes = [];
        $sortOrder = [];
        if ($nodesData) {
            try {
                $nodesData = $this->jsonHelper->jsonDecode($nodesData);
            } catch (\Zend_Json_Exception $e) {
                $nodesData = null;
            }
            if (!empty($nodesData)) {
                foreach ($nodesData as $row) {
                    if (isset($row['page_exists']) && $row['page_exists']) {
                        $appendToNodes[$row['node_id']] = 0;
                    }

                    if (isset($appendToNodes[$row['parent_node_id']])) {
                        if (strpos($row['node_id'], '_') !== false) {
                            $appendToNodes[$row['parent_node_id']] = $row['sort_order'];
                        } else {
                            $sortOrder[$row['node_id']] = $row['sort_order'];
                        }
                    }
                }
            }
        }

        return [
            'appendToNodes' => $appendToNodes,
            'sortOrder' => $sortOrder
        ];
    }
}
