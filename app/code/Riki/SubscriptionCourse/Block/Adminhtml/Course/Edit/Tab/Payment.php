<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Payment extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Riki\SubscriptionCourse\Model\Course\Source\Payment $sourcePayment
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\SubscriptionCourse\Model\Course\Source\Payment $sourcePayment,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_sourcePayment = $sourcePayment;
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Riki\SubscriptionCourse\Model\Course $model */
        $model = $this->_coreRegistry->registry('subscription_course');
        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('payment_fieldset', ['legend' => __('Subscription Course Payment')]);

        $fieldset->addField(
            'payment_ids',
            'multiselect',
            [
                'name'     => 'payment_ids[]',
                'label'     => __('Payment'),
                'title'     => __('Payment'),
                'required' => true,
                'values'   => $this->_sourcePayment->toOptionArray()
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
        return __('Payment');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Payment');
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
