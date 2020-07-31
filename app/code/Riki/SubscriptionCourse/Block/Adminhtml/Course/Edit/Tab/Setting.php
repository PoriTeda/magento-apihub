<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Setting extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_subscriptionPageHelper;
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
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        array $data = []
    ) {
        $this->_subscriptionPageHelper = $subscriptionPageHelper;
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
        /** @var \Riki\SubscriptionCourse\Model\Course $model */
        $model = $this->_coreRegistry->registry('subscription_course');
        $isDisabled = false;
        if ($this->checkHanpukai($model)) {
            $isDisabled = true;
        }
        $this->_coreRegistry->register('hanpukai_disable_radion_in_setting_tab', $isDisabled);

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('setting_fieldset', ['legend' => __('Subscription Course Settings')]);

        $fieldset->addField(
            'allow_skip_next_delivery',
            'radios',
            [
                'name' => 'allow_skip_next_delivery',
                'id' => 'allow_skip_next_delivery',
                'label' => __('Allow skip next delivery'),
                'title' => __('Allow skip next delivery'),
                'values' => $model->getYesNo(),
                'required' => false,
                'style' => 'opacity: 0'
            ]
        );

        $fieldset->addField(
            'allow_change_next_delivery_date',
            'radios',
            [
                'name' => 'allow_change_next_delivery_date',
                'id' => 'allow_change_next_delivery_date',
                'label' => __('Allow to change next delivery date'),
                'title' => __('Allow to change next delivery date'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        $fieldset->addField(
            'allow_change_payment_method',
            'radios',
            [
                'name' => 'allow_change_payment_method',
                'id' => 'allow_change_payment_method',
                'label' => __('Allow to change payment method'),
                'title' => __('Allow to change payment method'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        $fieldset->addField(
            'allow_change_address',
            'radios',
            [
                'name' => 'allow_change_address',
                'id' => 'allow_change_address',
                'label' => __('Allow to change address'),
                'title' => __('Allow to change address'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        $fieldset->addField(
            'allow_change_product',
            'radios',
            [
                'name' => 'allow_change_product',
                'id' => 'allow_change_product',
                'label' => __('Allow to change product'),
                'title' => __('Allow to change product'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        $fieldset->addField(
            'allow_change_qty',
            'radios',
            [
                'name' => 'allow_change_qty',
                'id' => 'allow_change_qty',
                'label' => __('Allow to change qty'),
                'title' => __('Allow to change qty'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        $fieldset->addField(
            'allow_choose_delivery_date',
            'radios',
            [
                'name' => 'allow_choose_delivery_date',
                'id' => 'allow_choose_delivery_date',
                'label' => __('Allow choose delivery date on checkout'),
                'title' => __('Allow choose delivery date on checkout'),
                'values' => $model->getYesNo(),
                'required' => false,

            ]
        );

        // set default values
        if (!$model->getId()) {
            if (!$this->checkHanpukai($model)) {
                $model->setData('allow_skip_next_delivery', '1');
                $model->setData('allow_change_next_delivery_date', '1');
                $model->setData('allow_change_payment_method', '1');
                $model->setData('allow_change_address', '1');
                $model->setData('allow_change_product', '1');
                $model->setData('allow_change_qty', '1');
            }

            $model->setData('allow_choose_delivery_date', 1);
        }

        if ($this->checkHanpukai($model)) {
            $model->setData('allow_skip_next_delivery', '0');
            $model->setData('allow_change_product', '0');
            $model->setData('allow_change_qty', '0');
        }

        /*$fieldset->addField(
            'free_shipping',
            'select',
            [
                'name' => 'free_shipping',
                'id' => 'free_shipping',
                'label' => __('Free Shipping'),
                'title' => __('Free Shipping'),
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'required' => true,
            ]
        );*/

        $fieldRenderFrom = $fieldset->addField('add_js',
            'text',
            [
                'name' => 'add_js',
                'title' => __('Render Addjs')
            ]
        );


        $render = $this->getLayout()->createBlock('Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Renderer\AddJsDisabledRadio');
        $fieldRenderFrom->setRenderer($render);

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
        return __('Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Settings');
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

    public function checkHanpukai($model)
    {
        if($model->getId()) {
            if($this->_subscriptionPageHelper->getSubscriptionType($model->getId()) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return true;
            }
        } else {
            $type = $this->getRequest()->getParam('type');
            if ($type) {
                if ($type == 'hfixed' || $type == 'hsequence') {
                    return true;
                }
            }
        }

        return false;
    }

}
