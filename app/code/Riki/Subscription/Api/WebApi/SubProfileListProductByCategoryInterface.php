<?php
namespace Riki\Subscription\Api\WebApi;
/**
 * @api
 */
interface SubProfileListProductByCategoryInterface
{
    /**
     * @param string $courseId
     * @param string $categoriesId
     * @param string $page
     * @param string $limit
     * @param string $isCategoryHomePage
     * @return mixed
     */
    public function getListProductByCategories($courseId, $categoriesId, $page, $limit, $isCategoryHomePage);

    /**
     * @param string $profileId
     * @return mixed  []
     */
    public function getListCategories($profileId);

    /**
     * @param string $categoriesId
     * @return mixed
     */
    public function getListCategoriesRecommend($categoriesId);
}