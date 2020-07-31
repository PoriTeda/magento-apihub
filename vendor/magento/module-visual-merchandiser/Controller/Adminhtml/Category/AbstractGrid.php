<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Category;

use Magento\Framework\Exception\NotFoundException;

class AbstractGrid extends \Magento\Catalog\Controller\Adminhtml\Category\Grid
{
    /**
     * @var string
     */
    protected $blockClass;

    /**
     * @var string
     */
    protected $blockName;

    /**
     * Grid Action
     * Display list of products related to current category
     *
     * @return \Magento\Framework\Controller\Result\Raw
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->blockClass || !$this->blockName) {
            throw new NotFoundException(__('Page not found.'));
        }

        $category = $this->_initCategory(true);
        if (!$category) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

        /** @var \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\BlockInterface $block */
        $block = $this->layoutFactory->create()->createBlock(
            $this->blockClass,
            $this->blockName
        );
        $block->setPositionCacheKey(
            $this->getRequest()->getParam(\Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY, false)
        );

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $block->toHtml()
        );
    }
}
