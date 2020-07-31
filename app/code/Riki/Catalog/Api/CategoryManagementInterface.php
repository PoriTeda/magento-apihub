<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Api;

/**
 * @api
 */
interface CategoryManagementInterface
{
    /**
     * Get products assigned to category
     *
     * @param int $categoryId
     * @param int $subprofileID
     * @return string[]
     */
    /**
     * Get cateogry name
     *
     * @param $arrCatIds
     * @return mixed
     */
    public function getListCategoryNameByIds($arrCatIds);
}