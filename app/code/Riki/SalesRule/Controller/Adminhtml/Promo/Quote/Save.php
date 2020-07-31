<?php
namespace Riki\SalesRule\Controller\Adminhtml\Promo\Quote;
use Riki\Subscription\Model\Indexer\ProfileSimulatorSalesrule\Processor;
use Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile;

class Save extends \Magento\SalesRule\Controller\Adminhtml\Promo\Quote\Save
{
    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $_ruleModel;

    /**
     * @var \Riki\Rule\Helper\Data
     */
    protected $_ruleHelper;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_session;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_loggerInterface;

    protected $_processor;

    protected $_profile;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime
     */
    private $dateTimeFilter;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\SalesRule\Model\Rule $ruleModel,
        \Riki\Rule\Helper\Data $ruleHelper,
        \Psr\Log\LoggerInterface $loggerInterface,
        Processor $processor,
        Profile $profile,
        \Magento\Framework\Stdlib\DateTime\Filter\DateTime $dateTimeFilter
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);
        $this->_profile = $profile;
        $this->_processor = $processor;
        $this->_ruleModel = $ruleModel;
        $this->_ruleHelper = $ruleHelper;
        $this->_session = $context->getSession();
        $this->_loggerInterface = $loggerInterface;
        $this->dateTimeFilter = $dateTimeFilter;
    }

    /**
     * Promo quote save action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_ruleModel;

                $this->_eventManager->dispatch(
                    'adminhtml_controller_salesrule_prepare_save',
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
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong rule is specified.'));
                    }
                }

                // $this->_validateWbsAccountCode($model, $data, $id); // Fix: bug/RIKI-1570 - Remove validation

                $dataObject = new \Magento\Framework\DataObject($data);

                $validateResult = $model->validateData($dataObject);
                $validateResult = $this->_ruleHelper->validateDatetime(new \Magento\Framework\DataObject($data), $validateResult);

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $this->_session->setPageData($data);
                    $this->_redirect('sales_rule/*/edit', ['id' => $model->getId()]);
                    return;
                }

                if (isset(
                        $data['simple_action']
                    ) && $data['simple_action'] == 'by_percent' && isset(
                        $data['discount_amount']
                    )
                ) {
                    $data['discount_amount'] = min(100, $data['discount_amount']);
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);
                $model->loadPost($data);

                $useAutoGeneration = (int)(!empty($data['use_auto_generation']));
                $model->setUseAutoGeneration($useAutoGeneration);

                $this->_session->setPageData($model->getData());

                $model->setData('trigger_recollect_quote', true);

                if(!$model->getData('from_time')) {
                    $model->unsetData('from_time');
                }

                if(!$model->getData('to_time')) {
                    $model->unsetData('to_time');
                }

                $model->save();

                $this->messageManager->addSuccess(__('You saved the rule.'));

                $this->_profile->clearCacheBySalesrule($model->getId());
                $this->_processor->reindexRow($model->getId());

                $this->_session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('sales_rule/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('sales_rule/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('sales_rule/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('sales_rule/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_loggerInterface->critical($e);
                $this->_session->setPageData($data);
                $this->_redirect('sales_rule/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('sales_rule/*/');
    }

    /**
     * Duplicate validation  WBS and Account code when create/edit promotion.
     *
     * @param \Magento\SalesRule\Model\Rule $model
     * @param $data
     * @param $id
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _validateWbsAccountCode($model, $data, $id)
    {
        $rules = $model->getCollection();
        if ($id) {
            $rules->addFieldToFilter('rule_id', ['neq' => (int)$id]);
        }
        $rules->load();

        foreach ($rules as $rule) {
            if ($rule->getWbs() === $data['wbs']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('WBS code is duplicated with ' . $rule->getName()));
            }
            if ($rule->getAccountCode() === $data['account_code']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Account code is duplicated with ' . $rule->getName()));
            }
        }
    }
}