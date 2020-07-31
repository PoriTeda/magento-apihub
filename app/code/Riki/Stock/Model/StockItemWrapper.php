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

namespace Riki\Stock\Model;
use Riki\Stock\Api\Data\StockItemWrapperInterface;
use Magento\Framework\DataObject;

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
class StockItemWrapper extends DataObject implements StockItemWrapperInterface
{
    /**
     * Get Products
     *
     * @return \Riki\Stock\Api\Data\StockItemInterface[]
     */
    public function getProducts()
    {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * Set Products
     *
     * @param \string[] $products products
     *
     * @return $this
     */
    public function setProducts($products)
    {
        return $this->setData(self::PRODUCTS, $products);
    }
}
