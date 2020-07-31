<?php
namespace Riki\Subscription\Block\Adminhtml\Multiple\Category\Campaign\Edit\Tab;

use Riki\Subscription\Model\Config\Source\Course\Options;

/**
 * Class Main
 * @package Riki\Subscription\Block\Adminhtml\Multiple\Category\Campaign\Edit\Tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $wysiwygConfig;
    /**
     * @var SubscriptionCourse
     */
    protected $subscriptionCourseOption;
    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param SubscriptionCourse $subscriptionCourseOption
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        Options $subscriptionCourseOption,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->subscriptionCourseOption = $subscriptionCourseOption;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * Prepare a form
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('subscription_campaign');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('page_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('campaign_id', 'hidden', ['name' => 'campaign_id']);
        }
        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'id' => 'name',
                'label' => __('Campaign Name'),
                'title' => __('Campaign Name'),
                'required' => true,
                'maxlength' => 255,
            ]
        );
        $fieldset->addType(
            'category_type',
            'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category'
        );
        $fieldset->addField(
            'category_ids',
            'category_type',
            [
                'name' => 'category_ids',
                'id'   => 'category_ids',
                'css_class' => 'field-category_ids',
                'label' => __('Categories'),
                'title' => __('Categories'),
                'required' => true,
                'values'   => $model->getCategoryIds()
            ]
        );
        $fieldset->addField(
            'course_ids',
            'multiselect',
            [
                'label' => __('Excluded Subscription Courses'),
                'note' => __('Excluded Subscription Courses'),
                'name' => 'course_ids[]',
                'required' => false,
                'scope' => 'store',
                'values' => $this->subscriptionCourseOption->getAllOptionsRule()
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
        return __('Campaign Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * For ACL
     *
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
