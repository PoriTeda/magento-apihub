<?php
namespace Riki\Rma\Validator;

class ShipmentRejectCodRma extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const WARNING = 'warning';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::WARNING => 'Confirm whether was this order rejected or not. If it was rejected, please put shipment number into Return Order',
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
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * ShipmentRejectCodRma constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
    ){
        $this->dataHelper = $dataHelper;
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

        if (!$value->getData('rma_shipment_number')) {
            return false;
        }

        $shipment = $this->searchHelper
            ->getByOrderId($value->getData('order_id'))
            ->getByShipmentStatus(\Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED)
            ->getOne()
            ->execute($this->shipmentRepository);

        if (!$shipment) {
            return true;
        }

        $methodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($value);
        if ($methodCode != \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return true;
        }

        $this->_error(self::WARNING);

        return false;
    }

}