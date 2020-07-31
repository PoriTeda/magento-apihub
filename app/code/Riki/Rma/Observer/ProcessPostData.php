<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProcessPostData implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var array
     */
    protected $rmaAdditionFields = [
        'reason_id', 'refund_allowed', 'refund_method',
        'rma_shipment_number', 'returned_date', 'full_partial',
        'substitution_order', 'returned_warehouse', 'total_cancel_point',
        'total_cancel_point_adj', 'total_return_point', 'total_return_point_adj',
        'return_shipping_fee', 'return_shipping_fee_adj', 'return_payment_fee',
        'return_payment_fee_adj', 'total_return_amount', 'total_return_amount_adj',
        'total_return_point_adjusted', 'total_cancel_point_adjusted',
        'total_return_amount_adjusted', 'customer_point_balance', 'earned_point', 'returnable_point_amount',
        'refund_without_product'
    ];

    /**
     * @var array
     */
    protected $adjustedFields = [
        'return_shipping_fee_adjusted'  =>  [
            'return_shipping_fee',
            'return_shipping_fee_adj'
        ],
        'return_payment_fee_adjusted'  =>  [
            'return_payment_fee',
            'return_payment_fee_adj'
        ]
    ];

    /**
     * @var array
     */
    protected $rmaItemAdditionFields = [
        'return_amount', 'return_amount_adj',
        'return_wrapping_fee', 'return_wrapping_fee_adj'
    ];

    /**
     * @var array
     */
    protected $rmaIntegerFields = [
        'total_cancel_point', 'total_cancel_point_adj', 'total_return_point',
        'total_return_point_adj', 'return_shipping_fee', 'return_shipping_fee_adj',
        'return_payment_fee', 'return_payment_fee_adj', 'total_return_amount',
        'total_return_amount_adj', 'total_return_point_adjusted',
        'total_cancel_point_adjusted', 'total_return_amount_adjusted', 'earned_point', 'customer_point_balance',
        'refund_without_product'
    ];

    /**
     * @var array
     */
    protected $rmaItemIntegerFields = [
        'return_amount', 'return_amount_adj',
        'return_wrapping_fee', 'return_wrapping_fee_adj'
    ];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->rmaItemRepository = $rmaItemRepository;
        $this->request = $request;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Customized post data will be handled before a RMA is saved.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $rma \Magento\Rma\Model\Rma */
        $rma = $observer->getRma();

        if (!$rma->getUsePostData()) {
            return;
        }

        // Logger to trace if something wrong with the post data
        try {
            $postValue = $this->request->getPostValue();
            $totalRmaReturnAmountInclTax = $rma->getData('return_amount_excl_tax') + $rma->getData('return_tax_amount');
            $totalReturnPoint = $rma->getData('returnable_point_amount');
            if ($totalRmaReturnAmountInclTax > $totalReturnPoint) {
                if ($postValue['total_return_amount'] == 0
                    || $postValue['total_return_amount_adjusted'] ==0) {
                    $this->logger->info("NED-3866 Log post request to check for wrong amount data".
                        \Zend_Json::encode($postValue));
                }
            }
        } catch (\Exception $e) {
            $this->logger->info("NED-3866 Unable to write log ".$e->getMessage());
        }
        // End

        $this->validateRequestData($this->request->getPostValue());

        $saveRequest = $this->_processSaveRequest($this->request->getPostValue());

        foreach ($this->rmaAdditionFields as $field) {
            if (isset($saveRequest[$field]) && (!isset($saveRequest['item_default_overall_value']) || $rma->getData($field) === null)) {
                $rma->setData($field, $saveRequest[$field]);
            }
        }

        foreach ($this->adjustedFields as $adjustedField => $relationFields) {
            $adjAmount = 0;

            foreach ($relationFields as $relationField) {
                if (!isset($saveRequest[$relationField])) {
                    continue 2;
                }

                $adjAmount += $saveRequest[$relationField];
            }

            if (!isset($saveRequest['item_default_overall_value']) || $rma->getData($adjustedField) === null) {
                $rma->setData($adjustedField, $adjAmount);
            }
        }

        // these fields are available for existing RMA only
        if (!$this->registry->registry('rma_save_more_refund_data') && $rma->getId() && isset($saveRequest['items'])) {
            $items = [];
            foreach ($saveRequest['items'] as $itemId => $itemData) {
                try {
                    /* @var \Magento\Rma\Model\Item $item */
                    $item = $this->rmaItemRepository->getById($itemId);
                    foreach ($this->rmaItemAdditionFields as $field) {
                        if (!isset($itemData[$field])) {
                            continue;
                        }
                        $item->setData($field, $itemData[$field]);
                    }
                    $items[] = $item;
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
            $rma->setItems($items);
        }
    }

    /**
     * @param array $saveRequest
     */
    protected function validateRequestData(array $saveRequest)
    {
        array_walk($saveRequest, [$this, 'validateIntField'], $this->rmaIntegerFields);

        if (isset($saveRequest['items'])) {
            foreach ($saveRequest['items'] as $item) {
                array_walk($item, [$this, 'validateIntField'], $this->rmaItemIntegerFields);
            }
        }
    }

    /**
     * @param $value
     * @param $field
     * @param $intFields
     * @throws LocalizedException
     */
    protected function validateIntField($value, $field, $intFields)
    {
        if (in_array($field, $intFields)) {
            if (!is_numeric($value)) {
                throw new LocalizedException(__('Request data is invalid'));
            }
        }
    }

    /**
     * @param array $saveRequest
     *
     * @return array
     */
    protected function _processSaveRequest(array $saveRequest)
    {
        array_walk($saveRequest, [$this, 'castToInt'], $this->rmaIntegerFields);

        if (isset($saveRequest['items'])) {
            foreach ($saveRequest['items'] as &$item) {
                array_walk($item, [$this, 'castToInt'], $this->rmaItemIntegerFields);
            }
        }

        return $saveRequest;
    }

    /**
     * Cast value $v of key $k to integer type if $k exists in fields
     *
     * @param $v
     * @param $k
     * @param $intFields
     */
    public function castToInt(&$v, $k, $intFields)
    {
        if (in_array($k, $intFields)) {
            $v = intval($v);
        }
    }
}