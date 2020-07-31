<?php
namespace Riki\MachineApi\Block\Adminhtml\B2c\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $systemStore;

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
        $this->systemStore = $systemStore;
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
        $model = $this->_coreRegistry->registry('b2cmachineskus_item');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('b2cmachineskus_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Basic Information')]);

        if ($model->getId()) {
            $fieldset->addField('type_id', 'hidden', ['name' => 'type_id']);
        }

        $fieldset->addField(
            'type_code',
            'text',
            [
                'name' => 'type_code',
                'id' => 'type_code',
                'label' => __('Machine type code'),
                'title' => __('Machine type code'),
                'required' => true,
                'maxlength' => 25
            ]
        );
        $fieldset->addField(
            'type_name',
            'text',
            [
                'name' => 'type_name',
                'id' => 'type_name',
                'label' => __('Machine type name'),
                'title' => __('Machine type name'),
                'required' => true,
                'maxlength' => 255
            ]
        );
        $fieldset->addField(
            'category_error_message',
            'text',
            [
                'name' => 'category_error_message',
                'id' => 'category_error_message',
                'label' => __('Category error message'),
                'title' => __('Category error message'),
                'required' => true,
                'maxlength' => 50
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
        return __('Basic Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Basic Information');
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
