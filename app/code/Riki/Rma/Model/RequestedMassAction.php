<?php
namespace Riki\Rma\Model;

use Riki\Rma\Model\Config\Source\RequestedMassAction\Status;

class RequestedMassAction extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'rma_mass_action';

    protected $_eventObject = 'rma_mass_action';

    const INIT_STATUS = Status::STATUS_WAITING;

    /**
     * @var Status
     */
    protected $statusOptions;

    /**
     * @var \Riki\Rma\Helper\Status
     */
    protected $returnStatusHelper;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;


    /**
     * RequestedMassAction constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Status $statusOptions
     * @param \Riki\Rma\Helper\Status $returnStatusHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\Config\Source\RequestedMassAction\Status $statusOptions,
        \Riki\Rma\Helper\Status $returnStatusHelper,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->statusOptions = $statusOptions;
        $this->returnStatusHelper = $returnStatusHelper;
        $this->rmaRepository = $rmaRepository;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\ResourceModel\RequestedMassAction');
    }

    /**
     * @return array
     */
    public function getAllStatus()
    {
        return $this->statusOptions->getOptions();
    }

    /**
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function getRma()
    {
        return $this->rmaRepository->getById($this->getData('rma_id'));
    }
}