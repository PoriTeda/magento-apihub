<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules;

use Magento\VisualMerchandiser\Api\RuleManagerInterface;
use Magento\VisualMerchandiser\Api\RuleFactoryPoolInterface;

class Factory
{

    /**
     * @deprecated  This property exists to provide backward compatibility.
     *              Now objects are created by RuleFactoryPoolInterface collection of factories
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @deprecated 100.2.0 This property exists to provide backward compatibility.
     *             Avoid usage of injected shared model instance.
     *             Create a new instance with a factory instead.
     * @see self::attribiteFactory
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    private $attributeFactory;
    /**
     * @var RuleManagerInterface
     */
    private $rulePool;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory = null,
        RuleFactoryPoolInterface $rulePool = null
    ) {
        $this->objectManager = $objectManager;
        $this->attribute = $attribute;
        $this->attributeFactory = $attributeFactory ?: $objectManager->get(
            \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory::class
        );

        $this->rulePool = $rulePool ?: $objectManager->get(
            RuleFactoryPoolInterface::class
        );
    }

    /**
     * @param string $str
     * @param array $noStrip
     * @return string
     */
    public static function classCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);

        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);

        return $str;
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    public function isBool($attributeCode)
    {
        // TODO: Need some better idea for specifying boolean datatype
        return in_array($attributeCode, [
            'links_purchased_separately'
        ]);
    }

    /**
     * @param array $rule
     * @return \Magento\VisualMerchandiser\Model\Rules\RuleInterface
     */
    public function create(array $rule)
    {
        $attribute = $this->attributeFactory->create()->loadByCode(
            \Magento\Catalog\Model\Product::ENTITY,
            $rule['attribute']
        );

        $ruleId = self::classCase($rule['attribute']);

        $args = [
            'rule' => $rule,
            'attribute' => $attribute,
        ];

        // Try load attribute type by his factory
        // or if it does not exist, load the generic factory classes
        if ($this->rulePool->hasRule($ruleId)) {
            $factory = $this->rulePool->getRule($ruleId);
            $handler = $factory->create($args);
        } else {
            if (!$attribute->usesSource()) {
                if ($this->isBool($rule['attribute'])) {
                    $factory = $this->rulePool->getRule(RuleFactoryPoolInterface::DEFAULT_RULE_BOOL);
                } else {
                    $factory = $this->rulePool->getRule(RuleFactoryPoolInterface::DEFAULT_RULE_LITERAL);
                }
            } else {
                $factory = $this->rulePool->getRule('Source');
            }

            $handler = $factory->create($args);
        }

        return $handler->get();
    }
}
