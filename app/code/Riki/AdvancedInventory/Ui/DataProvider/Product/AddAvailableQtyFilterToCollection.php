<?php
namespace Riki\AdvancedInventory\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

class AddAvailableQtyFilterToCollection implements AddFilterToCollectionInterface
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $config;

    /**
     * AddAvailableQtyFilterToCollection constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    )
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Collection $collection, $field, $condition = null)
    {
        if (isset($condition['gteq'])) {
            $collection->getSelect()->having(
                new \Zend_Db_Expr(AddAvailableQtyFieldToCollection::generateQtyColumnExpr($this->config->getValue('cataloginventory/item_options/manage_stock')) . ' >= ' . (float)$condition['gteq'])
            );
        }
        if (isset($condition['lteq'])) {
            $collection->getSelect()->having(
                new \Zend_Db_Expr(AddAvailableQtyFieldToCollection::generateQtyColumnExpr($this->config->getValue('cataloginventory/item_options/manage_stock')) . '<= ' . (float)$condition['lteq'])
            );
        }
    }
}
