<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products;

class HanpukaiFixed extends \Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\ProductsAbstract
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
                'type' =>'input',
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
            if(!empty($course->getHanpukaiFixedProductsDataPieceCase())) {
                foreach ($course->getHanpukaiFixedProductsDataPieceCase() as $productId   =>   $value) {

                    try{
                        $product  = $this->_productRepository->getById($productId);
                    }
                    catch(\Exception $e){
                        $product = null;
                    }

                    $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE;
                    $unitQty = 1;
                    $qty = (int)$value['qty'];

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


                    $this->_productsData[$productId] = ['qty' => $qty,'unit_qty' => $unitQty,'unit_case' => strtolower($unitCase)];
                }
            }
        }

        return $this->_productsData;
    }

    /**
     * @return string
     */
    public function getGridUrl() {
        return $this->getUrl('*/hanpukai/fixedGrid', ['_current' => true]);
    }

}