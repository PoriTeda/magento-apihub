<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Model;

use Magento\Rule\Model\AbstractModel;
use Mirasvit\FraudCheck\Model\Rule\Condition\CombineFactory as ConditionCombineFactory;
use Mirasvit\FraudCheck\Model\Rule\Action\CollectionFactory as ActionCollectionFactory;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @method ResourceModel\Rule getResource()
 *
 * @method string getName()
 * @method bool getIsActive()
 * @method string getStatus()
 */
class Rule extends AbstractModel
{
    /**
     * @var ConditionCombineFactory
     */
    protected $conditionCombineFactory;

    /**
     * @var ActionCollectionFactory
     */
    protected $actionCollectionFactory;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @param ConditionCombineFactory $conditionCombineFactory
     * @param ActionCollectionFactory $actionCollectionFactory
     * @param Context $context
     * @param ModelContext $modelContext
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        ConditionCombineFactory $conditionCombineFactory,
        ActionCollectionFactory $actionCollectionFactory,
        Context $context,
        ModelContext $modelContext,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate
    ) {
        $this->conditionCombineFactory = $conditionCombineFactory;
        $this->actionCollectionFactory = $actionCollectionFactory;
        $this->context = $context;

        parent::__construct($modelContext, $registry, $formFactory, $localeDate);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Mirasvit\FraudCheck\Model\ResourceModel\Rule');
        $this->setIdFieldName('rule_id');
    }

    /**
     * @return Rule\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->conditionCombineFactory->create();
    }

    /**
     * @return Rule\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->actionCollectionFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function toString($format = '')
    {
        $this->load($this->getId());
        $string = $this->getConditions()->asStringRecursive();

        $string = nl2br(preg_replace('/ /', '&nbsp;', $string));

        return $string;
    }

    /**
     * @return false|string
     */
    public function getFraudStatus()
    {
        $result = $this->validate($this->context->order);

        if ($result) {
            return $this->getStatus();
        }

        return false;
    }
}
