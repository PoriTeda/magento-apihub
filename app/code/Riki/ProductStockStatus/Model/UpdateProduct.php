<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_ProductStockStatus
 * @package  Riki\ProductStockStatus\Model
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */

namespace Riki\ProductStockStatus\Model;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Riki\ProductStockStatus\Model\ResourceModel\StockStatus\CollectionFactory
    as StockStatusCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class UpdateProduct
 *
 * @category Riki_ProductStockStatus
 * @package  Riki\ProductStockStatus\Model
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class UpdateProduct
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var
     */
    protected $stockStatusFactory;
    /**
     * @var
     */
    protected $productResource;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * UpdateProduct constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct
    (
        CollectionFactory $collectionFactory,
        StockStatusCollectionFactory $stockFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
       $this->productCollectionFactory = $collectionFactory;
       $this->stockStatusFactory = $stockFactory;
       $this->productResource = $productFactory;
       $this->searchCriteriaBuilder = $searchCriteriaBuilder;
       $this->productRepository = $productRepository;
    }

    /**
     * set default value
     */
    public function setProductDefaultValue()
    {
        $attributeName = 'stock_display_type';
        $stockId = $this->getFirstStockValue();
        $productCollection = $this->productCollectionFactory->create();
        if($productCollection->getSize()) {
            foreach ($productCollection as $product) {
                if(!$product->getData($attributeName))
                {
                    $product->setData($attributeName, $stockId);
                    $productResource = $this->productResource->create();
                    try {
                        $productResource->saveAttribute($product, $attributeName);
                    } catch (\Exception $e) {
                        throw  $e;
                    }
                }
            }
        }

    }

    /**
     * @return mixed
     */
    public function getFirstStockValue()
    {
        $collection = $this->stockStatusFactory->create();
        return $collection->getFirstItem()->getId();
    }

    /**
     * @param $deleteId
     * @throws \Exception
     */
    public function updateAttributeValue($deleteId)
    {
        //get first value
        $firstId = $this->stockStatusFactory->create()->getFirstItem()->getId();
        $attributeName = 'stock_display_type';
        $filter = $this->searchCriteriaBuilder->addFilter($attributeName,$deleteId)->create();
        $productList = $this->productRepository->getList($filter);
        if($productList->getTotalCount())
        {
            foreach($productList->getItems() as $product)
            {
                $product->setData($attributeName, $firstId);
                $productResource = $this->productResource->create();
                try {
                    $productResource->saveAttribute($product, $attributeName);
                } catch (\Exception $e) {
                    throw  $e;
                }
            }
        }

    }
}