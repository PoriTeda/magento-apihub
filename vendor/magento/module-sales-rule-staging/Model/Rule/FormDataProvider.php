<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Rule;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Data provider for sales rule update form.
 */
class FormDataProvider extends \Magento\SalesRule\Model\Rule\DataProvider
{
    /**
     * List of ignored fields
     *
     * @var string[]
     */
    protected $ignoredFields = [
        'rule_information/children/coupon_type'
    ];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $salesRuleFactory;

    /**
     * Initialize dependencies.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\Rule\Metadata\ValueProvider $metadataValueProvider
     * @param \Magento\Staging\Model\Entity\DataProvider\MetadataProvider $metaDataProvider
     * @param \Magento\SalesRule\Model\RuleFactory $salesRuleFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $meta
     * @param array $data
     * @param array $ignoredFields
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\SalesRule\Model\Rule\Metadata\ValueProvider $metadataValueProvider,
        \Magento\Staging\Model\Entity\DataProvider\MetadataProvider $metaDataProvider,
        \Magento\SalesRule\Model\RuleFactory $salesRuleFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = [],
        array $ignoredFields = []
    ) {
        $meta = array_replace_recursive($meta, $metaDataProvider->getMetadata());
        $this->request = $request;
        $this->ignoredFields = array_merge($this->ignoredFields, $ignoredFields);
        $this->salesRuleFactory = $salesRuleFactory;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $registry,
            $metadataValueProvider,
            $meta,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getMetadataValues()
    {
        $id = $this->request->getParam('id');
        $rule = $this->salesRuleFactory->create();
        $rule->load($id);

        $values = $this->metadataValueProvider->getMetadataValues($rule);
        foreach ($this->ignoredFields as $path) {
            $this->removeElement($values, $path);
        }
        return $values;
    }

    /**
     * Remove array element by path
     *
     * @param array $values
     * @param string $path
     * @return void
     */
    private function removeElement(&$values, $path)
    {
        $pieces = explode('/', $path);
        $i = 0;
        while ($i < count($pieces) - 1) {
            $piece = $pieces[$i];
            if (!is_array($values) || !array_key_exists($piece, $values)) {
                return;
            }
            $values = &$values[$piece];
            $i++;
        }
        $piece = end($pieces);
        unset($values[$piece]);
    }
}
