<?php

namespace Riki\SubscriptionMachine\Block\Adminhtml\Customer\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineConditionRule
     */
    protected $machineRuleConditionModel;

    /**
     * @var \Riki\Customer\Model\StatusMachine
     */
    protected $machineStatusSource;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Riki\SubscriptionMachine\Model\MachineConditionRule $machineRuleConditionModel
     * @param \Riki\Customer\Model\StatusMachine $machineStatusSource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Riki\SubscriptionMachine\Model\MachineConditionRule $machineRuleConditionModel,
        \Riki\Customer\Model\StatusMachine $machineStatusSource,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->machineRuleConditionModel = $machineRuleConditionModel;
        $this->machineStatusSource = $machineStatusSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('machinecustomer_item');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('machinecustomer_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Machine Customer Form')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        
        /* Add SKU */
        $addButtonSearchProduct    = [
            'label' => __('Search Product SKU'),
            'onclick' => "if($(machinecustomer_searchproduct).visible()){
                          $(machinecustomer_searchproduct).hide(); $$('.action-searchproduct span')[0].innerHTML = '".__('Search Product SKU')."';}else{
                          $(machinecustomer_searchproduct).show(); $$('.action-searchproduct span')[0].innerHTML = '".__('Hide Search Product SKU')."' ;}",
            'class' => 'action-add action-secondary action-searchproduct',
            'style' => 'margin-top:10px;'
        ];
        $fieldset->addField(
          'consumer_db_id',
            'text',
            [
                'name' => 'consumer_db_id',
                'id' => 'consumer_db_id',
                'label' => __('Customer ID'),
                'title' => __('Customer ID'),
                'required' => true
            ]
        );
        $fieldset->addField(
            'machine_type_code',
            'select',
            [
                'name' => 'machine_type_code',
                'id' => 'machine_type_code',
                'label' => __('Machine type code'),
                'title' => __('Machine type code'),
                'values' => $this->machineRuleConditionModel->getMachineCodeOptionArray(),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'sku',
            'text',
            [
                'name' => 'sku',
                'id' => 'sku',
                'label' => __('Sku'),
                'title' => __('Sku'),
                'after_element_html' => $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                    ->setData($addButtonSearchProduct)->toHtml()
            ]
        );
        $fieldSearchGridProductId = $fieldset->addField(
            'search_grid_product_id',
            'text',
            ['name' => 'search_grid_product_id',
                'label' => __('Product SKU'),
                'title' => __('Product SKU'),
                'required' => false,
            ]
        );

        $fieldSearchGridProductId->setRenderer($this->getLayout()->createBlock(
            '\Riki\SubscriptionMachine\Block\Adminhtml\Customer\Edit\SearchProduct'
        ));

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'id' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'class' => 'required-number',
                'values' => $this->machineStatusSource->getOptionArray(),
                'required' => true,
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
