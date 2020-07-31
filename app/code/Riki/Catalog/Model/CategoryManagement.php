<?php

namespace Riki\Catalog\Model;

use \Riki\Framework\Helper\Cache\FunctionCache;

class CategoryManagement implements \Riki\Catalog\Api\CategoryManagementInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;
    /**
     * @var array
     */
    protected $_allCategory = [];
    /**
     * @var FunctionCache
     */
    protected $functionCache;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        FunctionCache $cache
    )
    {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->functionCache = $cache;
    }

    /**
     * Get all category
     *
     * @return array
     */
    public function getAllCategory() {

        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->setPageSize(false);
        $arrCat =[];
        foreach($collection->getItems() as $cat) {
            $arrCat[$cat->getId()] = $cat->getName();
        }
        $this->functionCache->store($arrCat);

        return $arrCat;
    }

    /**
     * Get list category name
     *
     * @param $arrCatIds
     * @return array
     */
    public function getListCategoryNameByIds($arrCatIds)
    {
        $ids = is_array($arrCatIds) ? $arrCatIds : [$arrCatIds];
        if (!$ids) {
            return [];
        }

        return array_intersect_key( $this->getAllCategory(), array_flip($ids));
    }

}