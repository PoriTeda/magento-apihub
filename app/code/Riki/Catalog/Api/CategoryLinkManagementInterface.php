<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Api;

/**
 * @api
 */
interface CategoryLinkManagementInterface
{
    /**
     * Get products assigned to category
     *
     * @param int $categoryId
     * @param int $subprofileID
     * @return string[]
     */
    public function getAssignedProducts($categoryId,$subprofileID);
}