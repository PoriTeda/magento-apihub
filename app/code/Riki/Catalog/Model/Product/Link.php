<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\Link\CollectionFactory as LinkCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory as LinkProductCollectionFactory;
/**
 * Link.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Link extends \Magento\Catalog\Model\Product\Link
{
    protected $appState;

    /**
     * Link constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context                  Context
     * @param \Magento\Framework\Registry                                  $registry                 Registry
     * @param LinkCollectionFactory                                        $linkCollectionFactory    LinkCollectionFactory
     * @param LinkProductCollectionFactory                                 $productCollectionFactory LinkProductCollectionFactory
     * @param \Magento\CatalogInventory\Helper\Stock                       $stockHelper              Stock
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource                 AbstractResource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection       AbstractDb
     * @param array                                                        $data                     Array
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        LinkCollectionFactory $linkCollectionFactory,
        LinkProductCollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $linkCollectionFactory,
            $productCollectionFactory,
            $stockHelper,
            $resource,
            $resourceCollection,
            $data
        );
        $this->appState = $context->getAppState();
    }

    /**
     * Related Product does not show out of stock product
     *
     * @return mixed
     */
    public function getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create()->setLinkModel($this);
        if ($this->appState->getAreaCode() == 'frontend' && $this->getLinkTypeId() == self::LINK_TYPE_RELATED) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }
        return $collection;
    }
}
