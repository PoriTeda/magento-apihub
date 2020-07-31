<?php
namespace Riki\Preorder\Block\Adminhtml\Product\Edit\Tab\Inventory;
use Magento\Backend\Block\Widget\Form\Generic;
class PreOrder extends Generic
{
    protected $_yesNoSource;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        array $data = []
    ) {
        $this->_yesNoSource = $yesno;
        parent::__construct($context, $registry, $formFactory, $data);
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
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('riki_preorder_fieldset', ['legend' => __('Pre-Order')]);
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'fulfilment_date',
            'date',
            [
                'name' => 'product[fulfilment_date]',
                'label' => __('Fulfillment Date'),
                'title' => __('Fulfillment Date'),
                'required' => false,
                'date_format' => $dateFormat
            ]
        );
        $fieldset->addField(
            'riki_preorder_note',
            'text',
            ['name' => 'product[riki_preorder_note]',
                'label' => __('Pre-Order Note'),
                'title' => __('Pre-Order Note'),
                'required' => false
            ]
        );
        $fieldset->addField(
            'riki_preorder_cart_label',
            'text',
            [
                'name' => 'product[riki_preorder_cart_label]',
                'label' => __('Custom Pre-Order Cart Note'),
                'title' => __('Custom Pre-Order Cart Note'),
                'required' => false
            ]
        );
        $form->setValues($this->getProduct()->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}