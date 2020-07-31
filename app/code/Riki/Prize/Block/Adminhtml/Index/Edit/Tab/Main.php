<?php
namespace Riki\Prize\Block\Adminhtml\Index\Edit\Tab;

use Riki\Prize\Model\Prize\IsActive;
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
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

        $model = $this->_coreRegistry->registry('prize_item');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('prize_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Prize Form')]);

        if ($model->getId()) {
            $fieldset->addField('prize_id', 'hidden', ['name' => 'prize_id']);
        }
        
        $addButtonSearchCustomer    = [
            'label' => __('Search Customer ID'),
            'onclick' => "if($(prize_searchcustomer).visible()){
                          $(prize_searchcustomer).hide(); $$('.action-searchcustomer span')[0].innerHTML = '".__('Search Customer ID')."';}else{
                          $(prize_searchcustomer).show(); $$('.action-searchcustomer span')[0].innerHTML = '".__('Hide Search Customer ID')."' ;}",
            'class' => 'action-add action-secondary action-searchcustomer',
            'style' => 'margin-top:10px;'
        ];
        $fieldset->addField(
            'consumer_db_id',
            'text',
            [
                'name' => 'consumer_db_id',
                'id' => 'consumer_db_id',
                'label' => __('Consumer Id'),
                'title' => __('Consumer Id'),
                'required' => true,
                'after_element_html' => $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($addButtonSearchCustomer)->toHtml()
            ]
        );
        $fieldSearchGridCustomerId = $fieldset->addField(
            'search_grid_customer_id',
            'text',
            ['name' => 'search_grid_customer_id',
                'label' => __('Customer ID'),
                'title' => __('Customer ID'),
                'required' => false,
            ]
        );

        $fieldSearchGridCustomerId->setRenderer($this->getLayout()->createBlock('\Riki\Prize\Block\Adminhtml\Index\Edit\SearchCustomer'));
        /* Add SKU */
        $addButtonSearchProduct    = [
            'label' => __('Search Product SKU'),
            'onclick' => "if($(prize_searchproduct).visible()){
                          $(prize_searchproduct).hide(); $$('.action-searchproduct span')[0].innerHTML = '".__('Search Product SKU')."';}else{
                          $(prize_searchproduct).show(); $$('.action-searchproduct span')[0].innerHTML = '".__('Hide Search Product SKU')."' ;}",
            'class' => 'action-add action-secondary action-searchproduct',
            'style' => 'margin-top:10px;'
        ];
        $fieldset->addField(
            'sku',
            'text',
            [
                'name' => 'sku',
                'id' => 'sku',
                'label' => __('Sku'),
                'title' => __('Sku'),
                'required' => true,
                'after_element_html' => $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($addButtonSearchProduct)->toHtml()
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

        $fieldSearchGridProductId->setRenderer($this->getLayout()->createBlock('\Riki\Prize\Block\Adminhtml\Index\Edit\SearchProduct'));

        $fieldset->addField(
            'wbs',
            'text',
            [
                'name' => 'wbs',
                'id' => 'wbs',
                'label' => __('WBS'),
                'title' => __('WBS'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ]
        );
        $fieldset->addField(
            'qty',
            'text',
            [
                'name' => 'qty',
                'id' => 'qty',
                'label' => __('Qty'),
                'title' => __('Qty'),
                'class' => 'required-entry validate-number validate-greater-than-zero',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'id' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'values' => $model->getAvailableStatuses()
            ]
        );
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'winning_date',
            'date',
            [
                'name' => 'winning_date',
                'id' => 'winning_date',
                'label' => __('Winning Date'),
                'title' => __('Winning Date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat,
                'required' => true,
            ]
        );
        $fieldset->addField(
            'campaign_code',
            'text',
            [
                'name' => 'campaign_code',
                'id' => 'campaign_code',
                'label' => __('Campaign code'),
                'title' => __('Campaign code'),
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
        return __('Prize Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Prize Information');
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
