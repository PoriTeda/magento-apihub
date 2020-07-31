<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class AdditionalCategory extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
     * AdditionalCategory constructor.
     *
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

        $fieldset = $form->addFieldset('additional_category_fieldset', ['legend' => __('Subscription Additional Categories')]);

        $fieldset->addType('category_type', 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category');
        $fieldset->addField(
            'additional_category_ids',
            'category_type',
            [
                'name' => 'additional_category_ids',
                'css_class' => 'field-category_ids',
                'id'   => 'additional_category_ids',
                'label' => __('Additional Categories'),
                'title' => __('Additional Categories'),
                'values'   => $model->getAdditionalCategoryIds()
            ]
        );

        $fieldset->addField(
            'additional_category_description',
            'editor',
            [
                'name' => 'additional_category_description',
                'label' => __('Description'),
                'title' => __('Description'),
                'config' => $this->_getWysiwygConfig(),
                'wysiwyg' => true,
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
        return __('Additional Categories');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Additional Categories');
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

    /**
     * Get Wysiwyg Config
     *
     * @return \Magento\Framework\DataObject
     */
    protected function _getWysiwygConfig()
    {
        if ($this->_wysiwygConfig === null) {
            $this->_wysiwygConfig = $this->_wysiwygConfigModel->getConfig(
                ['tab_id' => $this->getTabId(), 'skip_widgets' => ['Magento\Banner\Block\Widget\Banner']]
            );
        }
        return $this->_wysiwygConfig;
    }
}
