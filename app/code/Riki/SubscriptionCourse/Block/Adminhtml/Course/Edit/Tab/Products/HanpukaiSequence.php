<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products;

class HanpukaiSequence extends \Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\ProductsAbstract
{
    protected $_productsData;

    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn(
            'delivery_number',
            [
                'header' => __('Delivery Number'),
                'name' => 'delivery_number',
                'type' => 'number',
                'validate_class' => 'required-entry validate-number validate-greater-than-zero',
                'index' => 'delivery_number',
                'editable' => true,
                'edit_only' => false,
                'header_css_class' => 'col-delivery-number',
                'column_css_class' => 'col-delivery-number'
            ]
        );

        $this->addColumn(
            'qty',
            [
                'header' => __('Quantity'),
                'name' => 'qty',
                'type' => 'number',
                'validate_class' => 'required-entry validate-number validate-greater-than-zero',
                'index' => 'qty',
                'editable' => true,
                'edit_only' => false,
                'header_css_class' => 'col-delivery-number',
                'column_css_class' => 'col-delivery-number',
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Hanpukai\Column\Qty'
            ]
        );

        $this->addColumn(
            'unit_case',
            [
                'header' => __('Unit'),
                'name' => 'unit_case',
                'index' => 'unit_case',
                'type' =>'number',
                'filter' => false,
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Hanpukai\Column\UnitCase'
            ]
        );

        return \Magento\Backend\Block\Widget\Grid\Extended::_prepareColumns();
    }

    /**
     * @return array
     */
    public function getSelectedProducts() {
        if(is_null($this->_productsData)){
            $course = $this->getSubscriptionCourse();
            $this->_productsData = [];

            if(!empty($course->getHanpukaiSequenceProductsData())) {
                foreach ($course->getHanpukaiSequenceProductsData() as $productId   =>   $productData) {

                    try{
                        $product  = $this->_productRepository->getById($productId);
                    }
                    catch(\Exception $e){
                        $product = null;
                    }

                    $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE;
                    $unitQty = 1;
                    $qty = (int)$productData['qty'];


                    if($product){
                        $unitQty = (null != $product->getData('unit_qty'))?$product->getData('unit_qty'):1;
                        if($product->getData('case_display') == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY){
                            $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE;
                        }
                        else{
                            $unitQty = 1;
                        }

                        if(\Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE == $unitCase){
                            $qty = (int)($qty/($unitQty));
                        }
                    }

                    $this->_productsData[$productId] = ['delivery_number' => $productData['delivery_number'], 'qty' => $qty,'unit_qty' => $unitQty,'unit_case' => strtolower($unitCase)];
                }
            }

        }

        return $this->_productsData;
    }

    /**
     * @return string
     */
    public function getGridUrl() {
        return $this->getUrl('*/hanpukai/sequenceGrid', ['_current' => true]);
    }

}