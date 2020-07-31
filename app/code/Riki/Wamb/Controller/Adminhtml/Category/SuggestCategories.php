<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Wamb\Controller\Adminhtml\Category;

class SuggestCategories extends \Magento\Catalog\Controller\Adminhtml\Category\SuggestCategories
{

    public function isAllowed()
    {
        return true;
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setJsonData(
            $this->layoutFactory->create()->createBlock('Riki\Wamb\Block\Adminhtml\Category\Tree')
                ->getSuggestedCategoriesJson($this->getRequest()->getParam('label_part'))
        );
    }


}