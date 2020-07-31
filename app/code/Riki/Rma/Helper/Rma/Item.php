<?php
namespace Riki\Rma\Helper\Rma;

class Item extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * Item constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->functionCache = $functionCache;
        $this->attributeRepository = $attributeRepository;
        parent::__construct($context);
    }

    /**
     * Prepare data default for new Rma Item (to make sure pass validate module-rma)
     *
     * @return array
     */
    public function getDefaultData()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $data = [
            'status' => \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING,
            'qty_authorized' => 1,
            'qty_approved' => 1,
            'qty_returned' => 1,
            'unit_case' => \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_PIECE,
        ];
        $attributes = ['reason', 'condition', 'resolution'];
        foreach ($attributes as $attribute) {
            try {
                $attributeModel = $this->attributeRepository->get(\Magento\Rma\Model\Item::ENTITY, $attribute);
                $sourceModel = $attributeModel->getSource();
                if ($sourceModel instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) {
                    $options = $sourceModel->toOptionArray() ?: [null];
                } else {
                    $options = [['value' => null]];
                }
                $option = end($options);
                $data[$attribute] = $option['value'];
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $data[$attribute] = null;
            }
        }
        $this->functionCache->store($data);

        return $data;
    }
}
