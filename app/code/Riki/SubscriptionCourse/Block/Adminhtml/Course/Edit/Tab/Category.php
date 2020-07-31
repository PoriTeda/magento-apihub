<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Category extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('category_fieldset', ['legend' => __('Subscription Categories')]);

        $fieldset->addType('category_type', 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category');
        $fieldset->addField(
            'category_ids',
            'category_type',
            [
                'name' => 'category_ids',
                'id'   => 'category_ids',
                'label' => __('Categories'),
                'title' => __('Categories'),
                'values'   => $model->getCategoryIds()
            ]
        );

        $fieldset->addType('profile_category_type', 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category');
        $fieldset->addField(
            'profile_category_ids',
            'profile_category_type',
            [
                'name' => 'profile_category_ids',
                'id'   => 'profile_category_ids',
                'css_class' => 'field-category_ids',
                'label' => __('Subscription categories for profile edit'),
                'title' => __('Subscription categories for profile edit'),
                'values'   => $model->getCategoryIds()
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
        return __('Categories');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Categories');
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
