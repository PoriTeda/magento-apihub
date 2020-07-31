<?php
namespace Riki\Wamb\Model\Config\Source;

class CategoryIds extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * CategoryIds constructor.
     *
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ){
        $this->categoryFactory = $categoryFactory; // Riki_Catalog should support repository get list
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $options = [];

        $collection = $this->categoryFactory->create()
            ->getCollection()
            ->addAttributeToSelect('name');

        /** @var \Magento\Catalog\Model\Category */
        foreach ($collection as $category) {
            $options[$category->getId()] = $category->getName();
        }

        return $options;
    }
}