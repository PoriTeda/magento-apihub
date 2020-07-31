<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class MergeProfile extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $course;

    /**
     * MergeProfile constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Riki\SubscriptionCourse\Model\Course $course,
        array $data = []
    ) {
        $this->course = $course;
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

        $fieldset = $form->addFieldset('merge_profile_fieldset', ['legend' => __('Merge Profile To')]);

        $listCourse = $this->course->getCoursesForForm();
        $courseId = $model->getId();
        $result = array_keys(array_column($listCourse,'value'),$courseId);
        if(sizeof($result) > 0 ) {
            unset($listCourse[$result[0]]);
        }


        $fieldset->addField(
            'merge_profile_to',
            'multiselect',
            [
                'name' => 'merge_profile_to[]',
                'label' => __('Merge Profile To'),
                'title' => __('Merge Profile To'),
                'values' => $listCourse,
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
        return __('Merge Profile To');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Merge Profile To');
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
