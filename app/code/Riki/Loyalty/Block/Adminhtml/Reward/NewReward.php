<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward;

use Magento\Customer\Controller\RegistryConstants;

class NewReward extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->setUseContainer(true);
    }

    /**
     * Form preparation
     *
     * @return void
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => ['id' => 'new_rewards_form', 'class' => 'admin__scope-old']]);
        $form->setUseContainer($this->getUseContainer());

        $customerId = (int)$this->getRequest()->getParam('id');

        $form->addField('new_rewards_messages', 'note', []);

        $fieldset = $form->addFieldset('new_rewards_form_fieldset', []);

        $fieldset->addField(
            'new_rewards_amount',
            'text',
            [
                'label'     => __('Amount'),
                'title'     => __('Amount'),
                'class'     => 'validate-number validate-not-negative-number validate-greater-than-zero',
                'required'  => true,
                'name'      => 'new_rewards_amount'
            ]
        );

        $fieldset->addField(
            'booking_point_wbs',
            'text',
            [
                'label' => __('WBS'),
                'title' => __('WBS'),
                'required' => true,
                'class' => 'validate-wbs-code',
                'name' => 'booking_point_wbs',
            ]
        );

        $fieldset->addField(
            'booking_point_account',
            'text',
            [
                'label' => __('Account code'),
                'title' => __('Account code'),
                'required' => true,
                'name' => 'booking_point_account',
            ]
        );

        $fieldset->addField(
            'expiration_period',
            'text',
            [
                'label' => __('Expiry period in (days)'),
                'title' => __('Expiry period in (days)'),
                'class'     => 'validate-number integer validate-not-negative-number',
                'required' => false,
                'name' => 'expiration_period',
            ]
        );

        $fieldset->addField(
            'new_rewards_comment',
            'textarea',
            [
                'label' => __('Comment'),
                'title' => __('Comment'),
                'required' => true,
                'name' => 'new_rewards_comment',
            ]
        );

        $fieldset->addField(
            'new_rewards_customer',
            'hidden',
            [
                'name' => 'new_rewards_customer',
                'value' => $customerId
            ]
        );


        $this->setForm($form);
    }

    /**
     * Attach new category dialog widget initialization
     *
     * @return string
     */
    public function getAfterElementHtml()
    {

        $widgetOptions = $this->_jsonEncoder->encode(
            [
                'saveRewardUrl' => $this->getUrl('riki_loyalty/reward/new'),
                'customerId'      => (int)$this->getRequest()->getParam('id'),
                'customerCode' =>  $this->_coreRegistry->registry('current_customer_code')
            ]
        );
        //TODO: JavaScript logic should be moved to separate file or reviewed
        return <<<HTML
<script>
require(["jquery","mage/mage", "Riki_Loyalty/js/add-point-popup"],function($) {  // waiting for dependencies at first
    $(function(){ // waiting for page to load to have '#category_ids-template' available
        $('#reward_form').newPointPopup($widgetOptions);
    });
});
</script>
HTML;
    }
}
