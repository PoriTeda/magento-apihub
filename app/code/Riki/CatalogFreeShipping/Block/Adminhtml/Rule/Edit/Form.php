<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogFreeShipping\Block\Adminhtml\Rule\Edit;

use Magento\Store\Model\System\Store;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject as ObjectConverter;

/**
 * Class Form
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * System st
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * Group r
     *
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Search criteria builder
     *
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Customer membership source
     *
     * @var \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership
     */
    protected $customerMembershipSource;


    /**
     * Constructor
     *
     * @param Store                                                                   $systemStore           system store
     * @param GroupRepositoryInterface                                                $groupRepository       group repository
     * @param SearchCriteriaBuilder                                                   $searchCriteriaBuilder search criteria builder
     * @param ObjectConverter                                                         $objectConverter       object converter
     * @param \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembership    membership source
     * @param \Magento\Backend\Block\Template\Context                                 $context               context
     * @param \Magento\Framework\Registry                                             $registry              registry
     * @param \Magento\Framework\Data\FormFactory                                     $formFactory           form factory
     * @param array                                                                   $data                  data
     *
     * @return self
     */
    public function __construct(
        Store $systemStore,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ObjectConverter $objectConverter,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembership,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {

        $this->systemStore           = $systemStore;
        $this->groupRepository       = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_objectConverter      = $objectConverter;
        $this->customerMembershipSource = $customerMembership;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );

    }//end __construct()


    /**
     * Retrieve template object
     *
     * @return \Magento\Newsletter\Model\Template
     */
    public function getModel()
    {
        return $this->_coreRegistry->registry('_current_rule');

    }//end getModel()


    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->getModel();

        /*
            * @var \Magento\Framework\Data\Form $form
        */

        $form = $this->_formFactory->create(
            [
             'data' => [
                        'id'     => 'edit_form',
                        'action' => $this->getData('action'),
                        'method' => 'post',
                       ],
            ]
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
             'legend' => __('Rule Information'),
             'class'  => 'fieldset-wide',
            ]
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id', 'value' => $model->getId()]);
        }

        if ($this->_storeManager->isSingleStoreMode()) {
            $websiteId = $this->_storeManager->getStore(true)->getWebsiteId();
            $fieldset->addField('website_ids', 'hidden', ['name' => 'website_ids[]', 'value' => $websiteId]);
            $model->setWebsiteIds($websiteId);
        } else {
            $field    = $fieldset->addField(
                'website_ids',
                'multiselect',
                [
                 'name'     => 'website_ids[]',
                 'label'    => __('Websites'),
                 'title'    => __('Websites'),
                 'required' => true,
                 'values'   => $this->systemStore->getWebsiteValuesForForm(),
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        }//end if

        $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create())
            ->getItems();
        $fieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
             'name'     => 'customer_group_ids[]',
             'label'    => __('Customer Groups'),
             'title'    => __('Customer Groups'),
             'required' => true,
             'values'   => $this->_objectConverter->toOptionArray($groups, 'id', 'code'),
            ]
        );

        $fieldset->addField(
            'memberships',
            'multiselect',
            [
             'name'     => 'memberships[]',
             'label'    => __('Customer Memberships'),
             'title'    => __('Customer Memberships'),
             'required' => true,
             'values'   => $this->customerMembershipSource->getAllOptions(),
            ]
        );

        $fieldset->addField(
            'ph_code',
            'text',
            [
             'name'  => 'ph_code',
             'label' => __('Ph Code'),
             'title' => __('Ph Code'),
            ]
        );

        $fieldset->addField(
            'sku',
            'text',
            [
             'name'  => 'sku',
             'label' => __('SKU'),
             'title' => __('SKU'),
            ]
        );

        $fieldset->addField(
            'wbs',
            'text',
            [
             'name'  => 'wbs',
             'label' => __('WBS'),
             'title' => __('WBS'),
                'required' => true,
                'class' =>  'validate-wbs-code'
            ]
        );

        $dateFormat = 'yyyy/MM/dd';
        $fieldset->addField(
            'from_date',
            'date',
            [
             'name'         => 'from_date',
             'label'        => __('From(yyyy/mm/dd)'),
             'title'        => __('From(yyyy/mm/dd)'),
             'date_format'  => $dateFormat,
            ]
        );

        $fieldset->addField(
            'from_hour',
            'select',
            [
             'label'   => __('From Hour'),
             'title'   => __('From Hour'),
             'name'    => 'from_hour',
             'options' => range(0, 23),
            ]
        );

        $fieldset->addField(
            'to_date',
            'date',
            [
             'name'         => 'to_date',
             'label'        => __('To(yyyy/mm/dd)'),
             'title'        => __('To(yyyy/mm/dd)'),
             'date_format'  => $dateFormat,
            ]
        );

        $fieldset->addField(
            'to_hour',
            'select',
            [
             'label'   => __('To Hour'),
             'title'   => __('To Hour'),
             'name'    => 'to_hour',
             'options' => range(0, 23),
            ]
        );

        $form->setValues($model->getData());

        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();

    }//end _prepareForm()


}//end class
