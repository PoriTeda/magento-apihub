<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Stock
 * @package  Riki\ProductStockStatus\Observer
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\ProductStockStatus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\ProductStockStatus\Model\StockStatusFactory;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
/**
 * Class UpdateProductThreshold
 *
 * @category Riki_Stock
 * @package  Riki\ProductStockStatus\Observer
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class UpdateProductThreshold implements ObserverInterface
{
    /**
     * @var StockStatusFactory
     */
    protected $stockStatusFactory;
    /**
     * @var ItemFactory
     */
    protected $stockItemFactory;

    /**
     * UpdateProductThreshold constructor.
     * @param StockStatusFactory $stockStatusFactory
     * @param ItemFactory $itemFactory
     */
    public function __construct(
        StockStatusFactory $stockStatusFactory,
        ItemFactory $itemFactory
    )
    {
        $this->stockItemFactory = $itemFactory;
        $this->stockStatusFactory = $stockStatusFactory;
    }

    /**
     * Execute function
     *
     * @param   Observer $observer
     * @throws  \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        //field name : stock_display_type
        $fieldName = 'stock_display_type';
        $stockId = $product->getData($fieldName);
        if($stockId) {
            $stockStatus = $this->stockStatusFactory->create()->load($stockId);
            $threshold = $stockStatus->getData('threshold');
            if($threshold)
            {
                $stockModel = $this->stockItemFactory->create()->load($product->getId(),'product_id');
                try
                {
                    $stockModel->setData('use_config_min_qty',0);
                    $stockModel->setData('min_qty',$threshold)->save();
                }
                catch(\Exception $e) {
                    throw $e;
                }
            }
        }
    }
}