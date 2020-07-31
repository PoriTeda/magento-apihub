<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminGridPrepareBefore implements ObserverInterface
{
    protected $_eavConfig;
    protected $_logger;

    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_eavConfig = $eavConfig;
        $this->_logger = $logger;
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
                'ph5_description',
                [
                    'header' => __('PH5 description'),
                    'index' => 'ph5_description',
                    'type' => 'options',
                    'options' => $this->getPhDescriptionOptions(),
                    'renderer'  => 'Riki\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\PhDescription',
                    'filter_condition_callback' => array($this, '_filterPhDescriptionCondition'),
                    'html_decorators' => ['nobr'],
                    'header_css_class' => 'col-period',
                    'column_css_class' => 'col-period'
                ],
                'qty'
            );

            $block->sortColumnsByOrder();
        }
    }

    /**
     * @param $collection
     * @param $column
     */
    public function _filterPhDescriptionCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->addFieldToFilter('ph5_description', array('finset' => $value));
    }

    /**
     * @return array
     */
    public function getPhDescriptionOptions()
    {
        $result = [];

        $attribute = $this->_eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'ph5_description');

        try{
            $options = $attribute->getSource()->getAllOptions();
            foreach($options as $option){
                $result[$option['value']] = $option['label'];
            }

            unset($result['']);
        }catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
        }

        return $result;
    }
}