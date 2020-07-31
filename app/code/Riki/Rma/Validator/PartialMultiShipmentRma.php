<?php
namespace Riki\Rma\Validator;

class PartialMultiShipmentRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const WARNING = 'warning';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::WARNING => 'Partial return with multiple shipment due to consumer request',
    ];

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * PartialMultiShipmentRma constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->searchHelper = $searchHelper;
        $this->shipmentRepository = $shipmentRepository;

        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        /** @var \Magento\Rma\Model\Rma $value */
        if (!$value instanceof \Magento\Rma\Model\Rma) {
            $this->_error(self::WARNING);
            return false;
        }

        if ($value->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL) {
            return true;
        }

        $shipmentCount = $this->searchHelper
            ->getByOrderId($value->getOrderId())
            ->getCount()
            ->execute($this->shipmentRepository);
        if ($shipmentCount <= 1) {
            return true;
        }

        $this->_error(self::WARNING);
        return false;
    }

}