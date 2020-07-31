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

namespace Riki\Stock\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

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
interface StockItemWrapperInterface extends ExtensibleDataInterface
{
    const PRODUCTS = 'products';

    /**
     * Get products
     *
     * @return \Riki\Stock\Api\Data\StockItemInterface[]
     */
    public function getProducts();

    /**
     * Set Product
     *
     * @param string[] $products products
     *
     * @return $this
     */
    public function setProducts($products);
}