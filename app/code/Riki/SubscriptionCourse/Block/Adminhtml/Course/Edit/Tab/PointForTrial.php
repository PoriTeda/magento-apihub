<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class PointForTrial extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfigModel;

    /**
     * WYSIWYG config data
     *
     * @var \Magento\Framework\DataObject
     */
    protected $_wysiwygConfig;

    /**
     * PointForTrial constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfigModel = $wysiwygConfig;
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
        /* @var $model \Riki\SubscriptionCourse\Model\Course */
        $model = $this->_coreRegistry->registry('subscription_course');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('point_for_trial_fieldset', ['legend' => __('Subscription Course Meta')]);

        $fieldset->addField(
            'point_for_trial',
            'text',
            [
                'name' => 'point_for_trial',
                'label' => __('Shopping Point Trial'),
                'title' => __('Shopping Point Trial'),
                'class' => 'validate-number'
            ]
        );

        $fieldset->addField(
            'point_for_trial_wbs',
            'text',
            [
                'name' => 'point_for_trial_wbs',
                'label' => __('Shopping Point WBS'),
                'title' => __('Shopping Point WBS'),
                'class' =>  'validate-wbs-code'
            ]
        );

        $fieldset->addField(
            'point_for_trial_account_code',
            'text',
            [
                'name' => 'point_for_trial_account_code',
                'label' => __('Account code'),
                'title' => __('Account code'),
                'class' =>  'validate-number'
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
        return __('Shopping Point Trial');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Shopping Point Trial');
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
