<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Model\Plugin;

class CategoryResource
{
    /**
     * Admin role
     *
     * @var \Magento\AdminGws\Model\Role
     */
    protected $_role;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     */
    public function __construct(\Magento\AdminGws\Model\Role $role)
    {
        $this->_role = $role;
    }

    /**
     * Check if category can be moved
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category $subject
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\Category $newParent
     * @param null|int $afterCategoryId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeChangeParent(
        \Magento\Catalog\Model\ResourceModel\Category $subject,
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\Category $newParent,
        $afterCategoryId = null
    ) {
        if (!$this->_role->getIsAll()) {
            /** @var $categoryItem \Magento\Catalog\Model\Category */
            foreach ([$newParent, $category] as $categoryItem) {
                if (!$this->_role->hasExclusiveCategoryAccess($categoryItem->getData('path'))) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('More permissions are needed to save this item.')
                    );
                }
            }
        }
    }
}
