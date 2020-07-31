<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Hanpukai extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        $model = $this->_coreRegistry->registry('subscription_course');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('website_fieldset', ['legend' => __('Hanpukai Manage')]);

        $fieldset->addField(
            'hanpukai_maximum_order_times',
            'text',
            [
                'name'     => 'hanpukai_maximum_order_times',
                'label'     => __('Maximum Order Times (Hanpukai)'),
                'title'     => __('Maximum Order Times (Hanpukai)'),
                'class' => 'validate-number',
                'required' => false,
                'id' => 'hanpukai_maximum_order_times',
            ]
        );

        $fieldset->addField(
            'hanpukai_delivery_date_allowed',
            'radios',
            [
                'name' => 'hanpukai_delivery_date_allowed',
                'id' => 'hanpukai_delivery_date_allowed',
                'label' => __('Can choose delivery date ?'),
                'title' => __('Can choose delivery date ?'),
                'values' => $model->getYesNo(),
                'required' => false
            ]
        );

        $dateFormat = 'yyyy/M/d';
        if($model->hasData('hanpukai_delivery_date_from')) {
            $datetime = new \DateTime($model->getData('hanpukai_delivery_date_from'));
            $model->setData('hanpukai_delivery_date_from', $datetime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone())));
        }
        $fieldset->addField(
            'hanpukai_delivery_date_from',
            'date',
            [
                'name' => 'hanpukai_delivery_date_from',
                'label' => __('Delivery Date From'),
                'title' => __('Delivery Date From'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'readonly' => 'readonly'
            ]
        );

        if($model->hasData('hanpukai_delivery_date_to') && $model->getData('hanpukai_delivery_date_to')) {
            $datetime = new \DateTime($model->getData('hanpukai_delivery_date_to'));
            $model->setData('hanpukai_delivery_date_to', $datetime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone())));
        }

        $fieldset->addField(
            'hanpukai_delivery_date_to',
            'date',
            [
                'name' => 'hanpukai_delivery_date_to',
                'label' => __('Delivery Date To'),
                'title' => __('Delivery Date To'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'readonly' => 'readonly'
            ]
        );

        if($model->hasData('hanpukai_first_delivery_date') && $model->getData('hanpukai_first_delivery_date')) {
            $datetime = new \DateTime($model->getData('hanpukai_first_delivery_date'));
            $model->setData('hanpukai_first_delivery_date', $datetime->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone())));
        }
        $fieldset->addField(
            'hanpukai_first_delivery_date',
            'date',
            [
                'name' => 'hanpukai_first_delivery_date',
                'label' => __('First Delivery Date'),
                'title' => __('First Delivery Date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'readonly' => 'readonly'
            ]
        );

        $fieldRenderFrom = $fieldset->addField('add_js',
            'text',
            [
                'name' => 'add_js',
                'title' => __('Render Addjs')
            ]
        );
        $render = $this->getLayout()->createBlock('Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Renderer\AddJsShowDelivery');
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
        return __('Hanpukai');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Hanpukai');
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
