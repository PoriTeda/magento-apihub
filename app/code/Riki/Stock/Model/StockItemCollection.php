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

use Riki\Stock\Api\Data\StockItemCollectionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
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
class StockItemCollection implements StockItemCollectionInterface
{


    protected $itemData;

    /**
     * Get items
     *
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface[]
     */
    public function getItems()
    {
        return $this->itemData;
    }

    /**
     * Set Items
     *
     * @param array $items items
     *
     * @inheritdoc
     *
     * @return void
     */
    public function setItems(array $items)
    {
        $this->itemData = $items;
    }

    /**
     * Get search criteria.
     *
     * @return void
     */
    public function getSearchCriteria()
    {

    }

    /**
     * Set Search Criteria
     *
     * @param SearchCriteriaInterface $searchCriteria searchCriteria
     *
     * @inheritdoc
     *
     * @return void
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {

    }

    /**
     * Get Total Count
     *
     * @inheritdoc
     *
     * @return void
     */
    public function getTotalCount()
    {

    }

    /**
     * Set Total Count
     *
     * @param int $totalCount totalCount
     *
     * @inheritdoc
     *
     * @return void
     */
    public function setTotalCount($totalCount)
    {

    }

}