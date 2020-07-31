<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer;

class Price extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * Type config
     *
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $typeConfig;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Riki\SubscriptionPage\Model\PriceBox
     */
    protected $priceBoxModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Riki\SubscriptionPage\Model\PriceBox $priceBoxModel,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->typeConfig = $typeConfig;
        $this->courseFactory = $courseFactory;
        $this->productRepository = $productRepository;
        $this->coreRegistry = $coreRegistry;
        $this->priceBoxModel = $priceBoxModel;
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (!$this->getColumn()->getData('course_id')) {
            return null;
        }
        $course = $this->courseFactory->create()->load($this->getColumn()->getData('course_id'));
        if (!$course->getId()) {
            return null;
        }
        $courseId = $course->getId();
        $frequencyId = $course->getFrequencies();
        $frequencyIdRegistry = $this->coreRegistry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        if ($frequencyIdRegistry && !in_array($frequencyIdRegistry, $frequencyId)) {
            $this->coreRegistry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
            $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId[0]);
        } elseif (!$frequencyIdRegistry) {
            $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId[0]);
        }
        if (!$this->coreRegistry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID)) {
            $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        }
        $product = $this->productRepository->getById($row->getData('product_id'));
        $product->setQty(1);
        $finalPrice = $this->priceBoxModel->getFinalProductPrice($product);
        if (!$finalPrice) {
            return null;
        }
        return $this->stripTags($finalPrice[1]);
    }

    public function stripTags($data, $allowableTags = null, $allowHtmlEntities = false)
    {
        return $this->filterManager->stripTags(
            $data,
            ['allowableTags' => $allowableTags, 'escape' => $allowHtmlEntities]
        );
    }
}
