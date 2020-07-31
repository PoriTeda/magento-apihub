<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class MultiMachine extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $course;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkus\Config\MachineTypeList
     */
    protected $machineTypeList;

    /**
     * MultiMachine constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Riki\MachineApi\Model\B2CMachineSkus\Config\MachineTypeList $machineTypeList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\MachineApi\Model\B2CMachineSkus\Config\MachineTypeList $machineTypeList,
        array $data = []
    ) {
        $this->machineTypeList = $machineTypeList;
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
        /** @var $model \Riki\SubscriptionCourse\Model\Course */
        $model = $this->_coreRegistry->registry('subscription_course');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('cou_');

        $fieldset = $form->addFieldset('machines_fieldset', ['legend' => __('Machines')]);

        $machineTypeList = $this->machineTypeList->getAllOptions();
        $fieldset->addField(
            'multi_machine',
            'multiselect',
            [
                'name' => 'multi_machine[]',
                'label' => __('Machine Types'),
                'title' => __('Machine Types'),
                'values' => $machineTypeList,
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
        return __('Machines');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Machines');
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
