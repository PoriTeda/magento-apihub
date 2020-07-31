<?php

namespace Riki\Loyalty\Model\Plugin\SaleRule;

use Magento\SalesRule\Api\Data\RuleExtensionFactory;

class ToDataModel
{
    /**
     * @var RuleExtensionFactory
     */
    protected $_ruleExtensionFactory;

    /**
     * ToDataModel constructor.
     * @param RuleExtensionFactory $ruleExtensionFactory
     */
    public function __construct(
        RuleExtensionFactory $ruleExtensionFactory
    ) {
        $this->_ruleExtensionFactory = $ruleExtensionFactory;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param \Magento\SalesRule\Model\Rule $ruleModel
     * @return \Magento\SalesRule\Model\Data\Rule
     */
    public function aroundToDataModel($subject, \Closure $proceed, \Magento\SalesRule\Model\Rule $ruleModel)
    {
        /** @var \Magento\SalesRule\Model\Data\Rule $result */
        $result = $proceed($ruleModel);
        $extensionAttributes = $result->getExtensionAttributes();
        if (is_null($extensionAttributes)) {
            $extensionAttributes = $this->_ruleExtensionFactory->create();
        }
        $extensionAttributes->setPointsDelta($ruleModel->getData('points_delta'));
        $extensionAttributes->setTypeBy($ruleModel->getData('type_by'));
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }
}