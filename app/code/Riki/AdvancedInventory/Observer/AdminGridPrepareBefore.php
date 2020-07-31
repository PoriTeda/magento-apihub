<?php
namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminGridPrepareBefore implements ObserverInterface
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $config;

    /**
     * AdminGridPrepareBefore constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    )
    {
        $this->config = $config;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getEvent()->getGrid();

        if($block instanceof \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid){

            $block->addColumnAfter(
                'as_available_qty',
                [
                    'header' => __('Qty'),
                    'index' => 'as_available_qty',
                    'type' => 'text',
                    'filter_condition_callback' => array($this, '_filterQtyCondition'),
                    'header_css_class' => 'col-period',
                    'column_css_class' => 'col-period'
                ],
                'price'
            );

            $block->sortColumnsByOrder();
        }
    }

    /**
     * @param $collection
     * @param $column
     */
    public function _filterQtyCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $collection->getSelect()->having(
            new \Zend_Db_Expr(\Riki\AdvancedInventory\Ui\DataProvider\Product\AddAvailableQtyFieldToCollection::generateQtyColumnExpr($this->config->getValue('cataloginventory/item_options/manage_stock')) . '= ' . $value)
        );
    }
}