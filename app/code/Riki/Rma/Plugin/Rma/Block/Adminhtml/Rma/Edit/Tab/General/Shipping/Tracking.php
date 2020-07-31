<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\ShipmentRepository;
class Tracking
{
    const DEFAULT_CARRIER_CODE_FOR_COD_METHOD = 'yupack';
    const DEFAULT_CARRIER_TITLE_FOR_COD_METHOD = 'Yu-Pack';
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Rma shipping collection
     *
     * @var \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory
     */
    protected $_shippingCollectionFactory;
    /**
     * @var \Magento\Rma\Model\ShippingFactory
     */
    private $shippingFactory;

    /**
     * Tracking constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepository $shipmentRepository
     * @param \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $shippingCollectionFactory
     * @param \Magento\Rma\Model\ShippingFactory $shippingFactory
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Shipping\Model\Config $shippingConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepository $shipmentRepository,
        \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $shippingCollectionFactory,
        \Magento\Rma\Model\ShippingFactory $shippingFactory,
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        $this->dataHelper = $dataHelper;
        $this->shippingConfig = $shippingConfig;
        $this->authorization = $authorization;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->_shippingCollectionFactory = $shippingCollectionFactory;
        $this->shippingFactory = $shippingFactory;
    }

    /**
     * Get carriers
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject
     * @param \Closure $proceed
     *
     * @return array|mixed
     */
    public function aroundGetCarriers(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject,
        \Closure $proceed
    ) {
        $rma = $this->dataHelper->getCurrentRma();
        if (!$rma instanceof \Magento\Rma\Model\Rma) {
            return $proceed();
        }

        $carriers = [];
        $carrierInstances = $this->shippingConfig->getAllCarriers($rma->getStoreId());
        /** @var \Magento\Shipping\Model\Carrier\CarrierInterface $carrier */
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }

        return $carriers;
    }

    public function checkPermissionReturn(){
        $flag = false;
        if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_save_w')){
            $flag = true;
        } else if ($this->authorization->isAllowed('Riki_Rma::rma_return_actions_save_cc')){
            $flag = true;
        }
        return $flag;
    }

    /**
     * check permission for add tracking info action
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject
     * @param $result
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject,
        $result
    ){
        if(!$this->checkPermissionReturn()){
            $subject->unsetChild('save_button');
        }

        return $result;
    }

    /**
     * check permission for remove tracking action
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject
     * @param $result
     * @return null
     */
    public function afterGetRemoveUrl(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Tracking $subject,
        $result
    ){
        if(!$this->checkPermissionReturn()){
            return null;
        }
        return $result;
    }
}