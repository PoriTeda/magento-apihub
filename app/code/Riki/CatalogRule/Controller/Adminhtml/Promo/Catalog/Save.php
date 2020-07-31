<?php

namespace Riki\CatalogRule\Controller\Adminhtml\Promo\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\CatalogRule\Model\Rule;
use Riki\Rule\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\CatalogRule\Model\Flag;
use Psr\Log\LoggerInterface;
use Riki\Subscription\Model\Indexer\ProfileSimulatorCatalogrule\Processor;
use Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile;
use Magento\Framework\Json\Helper\Data as HelperJson;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends \Magento\CatalogRule\Controller\Adminhtml\Promo\Catalog\Save
{
    /**
     * @var Rule
     */
    protected $_ruleModel;

    /**
     * @var Data
     */
    protected $_ruleHelper;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Flag
     */
    protected $_flag;

    /**
     * @var LoggerInterface
     */
    protected $_loggerInterface;

    protected $_processor;

    protected $_profile;

    protected $_helperJson;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime
     */
    private $dateTimeFilter;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    private $courseModel;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Date $dateFilter,
        DataPersistorInterface $dataPersistor,
        Rule $ruleModel,
        Data $ruleHelper,
        Flag $flag,
        LoggerInterface $loggerInterface,
        Processor $processor,
        Profile $profile,
        HelperJson $helperJson,
        \Magento\Framework\Stdlib\DateTime\Filter\DateTime $dateTimeFilter,
        \Riki\SubscriptionCourse\Model\Course $courseModel
    ) {
        parent::__construct($context, $coreRegistry, $dateFilter, $dataPersistor);
        $this->_helperJson = $helperJson;
        $this->_profile = $profile;
        $this->_processor = $processor;
        $this->_ruleModel = $ruleModel;
        $this->_ruleHelper = $ruleHelper;
        $this->_session = $context->getSession();
        $this->_flag = $flag;
        $this->_loggerInterface = $loggerInterface;
        $this->dateTimeFilter = $dateTimeFilter;
        $this->courseModel = $courseModel;
    }

    /**
     * Support time for Catalog Rule
     *
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_ruleModel;

                $this->_eventManager->dispatch(
                    'adminhtml_controller_catalogrule_prepare_save',
                    ['request' => $this->getRequest()]
                );
                $data = $this->getRequest()->getPostValue();

                $filterValues = [];

                if ($this->getRequest()->getParam('from_time')) {
                    $filterValues['from_time'] = $this->dateTimeFilter;
                }
                if ($this->getRequest()->getParam('to_time')) {
                    $filterValues['to_time'] = $this->dateTimeFilter;
                }

                $inputFilter = new \Zend_Filter_Input(
                    $filterValues ,
                    [],
                    $data
                );

                $data = $inputFilter->getUnescaped();

                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('Wrong rule specified.'));
                    }
                }

                $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
                $validateResult = $this->_ruleHelper->validateDatetime(new \Magento\Framework\DataObject($data), $validateResult);

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $this->_getSession()->setPageData($data);
                    $this->_redirect('catalog_rule/*/edit', ['id' => $model->getId()]);
                    return;
                }
                if (array_key_exists("rule",$data)) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                unset($data['rule']);

                $model->loadPost($data);

                // Update field apply_subscription_course_and_frequency before save.
                $applyData = [];
                if ($model->getData('subscription') != 1) {
                    $courseFrequencies = $this->courseModel->getCourseFrequencyList();
                    foreach ($model->getData('apply_subscription') as $courseId) {
                        $applyData[$courseId] = [];
                        foreach ($model->getData('apply_frequency') as $frequencyId) {
                            if (in_array($frequencyId, $courseFrequencies[$courseId])) {
                                $applyData[$courseId][] = $frequencyId;
                            }
                        }
                    }
                }
                $model->setData('apply_subscription_course_and_frequency', $applyData);

                if(!$model->getData('from_time')) {
                    $model->unsetData('from_time');
                }

                if(!$model->getData('to_time')) {
                    $model->unsetData('to_time');
                }

                $this->_session->setPageData($model->getData());

                $model->save();

                $this->messageManager->addSuccess(__('You saved the rule.'));

                // data contains catalogrule and subscription: course and frequency
                $catalogSubscriptionData = [];
                if ($model->getData('subscription') != 1) {
                    if(!empty($model->getData('course_frequency')))
                    {
                        $courseFrequencies = $this->_helperJson->jsonDecode($model->getData('course_frequency'));
                        foreach ($model->getData('apply_subscription') as $courseId) {
                            $catalogSubscriptionData[$courseId] = $courseFrequencies[$courseId];
                        }
                        $this->_profile->reindexProfileByCatalogrule($catalogSubscriptionData);
                        $this->_processor->reindexRow($model->getId());
                    }
                }

                $this->_session->setPageData(false);
                if ($this->getRequest()->getParam('auto_apply')) {
                    $this->getRequest()->setParam('rule_id', $model->getId());
                    $this->_forward('applyRules');
                } else {
                    if ($model->isRuleBehaviorChanged()) {
                        $this->_flag
                            ->loadSelf()
                            ->setState(1)
                            ->save();
                    }
                    if ($this->getRequest()->getParam('back')) {
                        $this->_redirect('catalog_rule/*/edit', ['id' => $model->getId()]);
                        return;
                    }
                    $this->_redirect('catalog_rule/*/');
                }
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_loggerInterface->critical($e);
                $this->_session->setPageData($data);
                $this->_redirect('catalog_rule/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('catalog_rule/*/');
    }
}