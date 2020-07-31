<?php
/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Stock\Api;

/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

interface StockRegistryInterface
{
    /**
     * Get stock item by sku
     *
     * @param string[] $products products
     *
     * @return \Riki\Stock\Api\Data\StockItemWrapperInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStockItemBySku($products);




}