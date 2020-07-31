<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab;
/**
 * Class Main
 * @package Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * fairType options
     * @param \Riki\FairAndSeasonalGift\Model\Options\FairType $fairType
     */
    protected $_fairType;

    /**
     * year options
     * @param \Riki\FairAndSeasonalGift\Model\Options\YearOption
     */
    protected $_yearOption;

    /**
     * customer membership options
     * @param Riki\SubscriptionMembership\Model\Customer\Attribute\Source
     */
    protected $_membershipOption;
    /**
     * Main constructor.
     * 
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
        \Riki\FairAndSeasonalGift\Model\Options\FairType $fairType,
        \Riki\FairAndSeasonalGift\Model\Options\YearOption $yearOption,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembershipType,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_fairType = $fairType;
        $this->_yearOption = $yearOption;
        $this->_membershipOption = $customerMembershipType;
    }

    /**
     * Prepare form
     * 
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Riki\Questionnaire\Model\Questionnaire $model */
        $model = $this->_coreRegistry->registry('current_fair_form');
        
        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('fair_');

        $fieldSet = $form->addFieldset('base_fieldset', ['legend' => __('Basic Information')]);

        if ($model->getFairId()) {
            $fieldSet->addField('fair_id', 'hidden', ['name' => 'fair_id']);
        }
        
        $fieldSet->addField(
            'fair_code',
            'text',
            [
                'name' => 'fair_code',
                'id' => 'fair_code',
                'label' => __('Fair Code'),
                'title' => __('Fair Code'),
                'class' => 'required-entry',
                'unique' => true,
                'required' => true
            ]
        );

        $fieldSet->addField(
            'fair_year',
            'select',
            [
                'label' => __('Fair Year'),
                'title' => __('Fair Year'),
                'name' => 'fair_year',
                'id' => 'fair_year',
                'required' => true,
                'values' => $this->_yearOption->toOptionArray()
            ]
        );

        $fieldSet->addField(
            'fair_type',
            'select',
            [
                'label' => __('Fair Type'),
                'title' => __('Fair Type'),
                'name' => 'fair_type',
                'id' => 'fair_type',
                'required' => true,
                'values' => $this->_fairType->toOptionArray()
            ]
        );
        
        $fieldSet->addField(
            'fair_name',
            'text',
            [
                'name' => 'fair_name',
                'id' => 'fair_name',
                'label' => __('Fair Name'),
                'title' => __('Fair Name'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);

        $fieldSet->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'id' => 'start_date',
                'label' => __('Start date'),
                'title' => __('End date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat,
                'class' => 'validate-date required-entry validate-date-range date-range-start_date-from',
                'required' => true
            ]
        );

        $fieldSet->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'id' => 'end_date',
                'label' => __('End date'),
                'title' => __('End date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat,
                'class' => 'validate-date required-entry validate-date-range date-range-end_date-to',
                'required' => true
            ]
        );

        $form->setValues($model->getData());

        $fieldSet->addField(
            'mem_ids',
            'multiselect',
            [
                'name' => 'mem_ids',
                'id' => 'mem_ids',
                'label' => __('Membership'),
                'title' => __('Membership'),
                'class' => 'required-entry',
                'multiple' => true,
                'values' => $this->_membershipOption->toOptionArray(),
                'required' => true,
                'value' => !empty($model->getMemIds()) ? explode(',',$model->getMemIds()) : []
             ]
        );

        $form->setFieldNameSuffix('fair');

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
        return __('Basic information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Basic information');
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