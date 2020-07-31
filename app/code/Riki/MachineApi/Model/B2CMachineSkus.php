<?php
namespace Riki\MachineApi\Model;

use Magento\Framework\Model\Context;
use Riki\CatalogRule\Api\ProductRepositoryInterface;

class B2CMachineSkus extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Catalog\Model\Product|null
     */
    protected $product;

    protected $pageSize = 20;
    protected $curPage = 0;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * B2CMachineSkus constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ResourceModel\B2CMachineSkus $b2CMachineSkusResourceModel
     * @param ResourceModel\B2CMachineSkus\Collection $b2CMachineSkusCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus $b2CMachineSkusResourceModel,
        \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Collection $b2CMachineSkusCollection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $b2CMachineSkusResourceModel, $b2CMachineSkusCollection, $data);
        $this->productRepository  = $productRepository;
    }

    protected function _construct()
    {
        $this->_init(\Riki\MachineApi\Model\ResourceModel\B2CMachineSkus::class);
    }

    /**
     * Validate data before save
     *
     * @return array|bool
     */
    public function validate()
    {
        $errors = [];
        if (!$this->getTypeId() && !$this->getMachines()) {
            $errors[] = __('Machines must be selected');
        }
        //check machine Type code is already exists
        if (!$this->getTypeId() && $typeCode = $this->getData('type_code')) {
            $machine = $this->getCollection()->addFieldToFilter('type_code', $typeCode)->load();
            if ($machine->getItems()) {
                $errors[] = __('Machine type code already exists');
            }
        }
        if (!$errors) {
            return true;
        }
        return $errors;
    }

    /**
     * Get product relation by sku
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if ($this->product) {
            return $this->product;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->get($this->getData('sku'));
        $this->product  = $product;
        return $this->product;
    }

    /**
     * Check if we can delete this winner prize
     *
     * @return bool
     */
    public function canDelete()
    {
        if (!$this->getId()) {
            return false;
        }
        $inProduct = $this->getResource()->isInProduct($this->getId());
        if ($inProduct) {
            return false;
        }
        $inCourse = $this->getResource()->isInSubscriptionCourse($this->getId());
        if ($inCourse) {
            return false;
        }
        return true;
    }

    /**
     * Get list machines belong to machine type
     *
     * @param int $typeId
     * @return array
     */
    public function getMachinesByMachineType($typeId)
    {
        return $this->getResource()->getMachinesByType($typeId);
    }

    public function getMachine($typeId, $productId)
    {
        return $this->getResource()->getMachine($typeId, $productId);
    }

    public function getMachineTypeOfMachine($machineIds)
    {
        return $this->getResource()->getMachineTypeOfMachine($machineIds);
    }

    /**
     * Get all Machines of Machine type
     */
    public function getProducts($curPage = 0, $pageSize = 20)
    {
        if (!$this->getId()) {
            return false;
        }
        $this->setCurPage($curPage);
        $this->setPageSize($pageSize);

        return $this->getResource()->getProducts($this);
    }

    /**
     * @return mixed
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param mixed $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @return mixed
     */
    public function getCurPage()
    {
        return $this->curPage;
    }

    /**
     * @param mixed $curPage
     */
    public function setCurPage($curPage)
    {
        $this->curPage = $curPage;
    }
}
