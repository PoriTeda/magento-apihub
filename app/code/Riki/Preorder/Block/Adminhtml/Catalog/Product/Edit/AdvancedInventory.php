<?php

namespace Riki\Preorder\Block\Adminhtml\Catalog\Product\Edit;

use Riki\Framework\Helper\Datetime;

/**
 * For Magento >= 2.1
 */
class AdvancedInventory extends \Magento\Ui\Component\Form\Fieldset
{

    protected $_fieldFactory = null;
    protected $_coreRegistry;
    protected $_datetime;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Ui\Component\Form\FieldFactory $fieldFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Framework\Helper\Datetime $datetime,
        array $components = [],
        array $data = []
    ) {

        parent::__construct($context, $components, $data);
        $this->_fieldFactory = $fieldFactory;
        $this->_coreRegistry = $registry;
        $this->_datetime = $datetime;
    }

    /**
     * Return current product instance
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    public function getChildComponents()
    {
        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                'config' => [
                    'label' => __('Fulfillment Date'),
                    'default' => $this->_datetime->fromDb($this->getProduct()->getData('fulfilment_date')),
                    //'value' => $this->_datetime->fromDb($this->getProduct()->getData('fulfilment_date')),
                    'formElement' => 'date',
                    'required' => false,
                    //'date_format' => $dateFormat
                ],
                'name' => "fulfilment_date"
            ]
        );

        $fieldInstance->prepare();
        $this->addComponent("fulfilment_date", $fieldInstance);


        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                'config' => [
                    'label' => __('Pre-Order Note'),
                    'value' => $this->getProduct()->getData('riki_preorder_note'),
                    'formElement' => 'input'
                ],
                'name' => "riki_preorder_note"
            ]
        );

        $fieldInstance->prepare();
        $this->addComponent("riki_preorder_note", $fieldInstance);

        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                'config' => [
                    'label' => __('Custom Pre-Order Cart Note'),
                    'value' => $this->getProduct()->getData('riki_preorder_cart_label'),
                    'formElement' => 'input'
                ],
                'name' => "riki_preorder_cart_label"
            ]
        );

        $fieldInstance->prepare();
        $this->addComponent("riki_preorder_cart_label", $fieldInstance);


        return parent::getChildComponents();
    }
}
