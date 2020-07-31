<?php
namespace Riki\CedynaInvoice\Helper;

use Riki\CedynaInvoice\Model\Source\Config\DataType;

/**
 * Class Validator
 * @package Riki\CedynaInvoice\Helper
 */
class Validator
{
    const TXT_FILE_ROW_START_CHARACTER = '2';
    /**
     * @var Data
     */
    private $helperData;
    /**
     * @var Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $rmaCollectionFactory;
    /**
     * @var Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * Validator constructor.
     * @param Data $helperData
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Riki\CedynaInvoice\Helper\Data $helperData,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        $this->helperData = $helperData;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * Validate required field
     *
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @return bool
     */
    private function validateRequire($value, $rowNumber, $fieldName)
    {
        if (!$value) {
            $this->helperData->writeToLog(
                __('Row[%1]: field [%2] is required field', $rowNumber, $fieldName)
            );
            return false;
        }
        return true;
    }

    /**
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @return bool
     */
    private function validateNumber($value, $rowNumber, $fieldName)
    {
        if (is_numeric($value)) {
            return true;
        } else {
            $this->helperData->writeToLog(
                __('Row[%1]: field [%2] is not a number', $rowNumber, $fieldName)
            );
            return false;
        }
    }
    /**
     * Validate positive integer number
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @return bool
     */
    private function validatePositiveNumber($value, $rowNumber, $fieldName)
    {
        $valueNumber = (int) $value;
        if (filter_var($valueNumber, FILTER_VALIDATE_INT) && $valueNumber >=0) {
            return true;
        } else {
            $this->helperData->writeToLog(
                __('Row[%1]: field [%2] is not a positive number', $rowNumber, $fieldName)
            );
            return false;
        }
    }
    /**
     * Validate max length
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @param $maxLength
     * @return bool
     */
    private function validateMaxlength($value, $rowNumber, $fieldName, $maxLength)
    {
        if (strlen(trim($value)) > $maxLength) {
            $this->helperData->writeToLog(
                __(
                    'Row[%1]: field [%2] is longer than [%3] characters',
                    $rowNumber,
                    $fieldName,
                    $maxLength
                )
            );
            return false;
        }
        return true;
    }

    /**
     * Validate date time format
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @return bool
     */
    private function validateDatetime($value, $rowNumber, $fieldName)
    {
        if (mb_strlen($value) == 6) {
            $year = (int)mb_substr($value, 0, 4);
            $month = (int)mb_substr($value, 4, 2);
            $day = 5;
        } else {
            $year = (int)mb_substr($value, 0, 4);
            $month = (int)mb_substr($value, 4, 2);
            $day = (int)mb_substr($value, 6, 2);
        }
        if (checkdate($month, $day, $year)) {
            return true;
        } else {
            $this->helperData->writeToLog(
                __('Row[%1]: field [%2] is not a date time format', $rowNumber, $fieldName)
            );
            return false;
        }
    }

    /**
     * Validate Data Type
     * @param $value
     * @param $rowNumber
     * @param $fieldName
     * @return bool
     */
    private function validateDataType($value, $rowNumber, $fieldName)
    {
        $types = [
            DataType::DATA_TYPE_OPTION_SALES,
            DataType::DATA_TYPE_OPTION_RETURN,
            DataType::DATA_TYPE_OPTION_DISCOUNT
        ];
        if (in_array($value, $types)) {
            return true;
        } else {
            $listValue = implode(',', $types);
            $this->helperData->writeToLog(
                __(
                    'Row[%1]: field [%2]  must be one of these values [%2]',
                    $rowNumber,
                    $fieldName,
                    $listValue
                )
            );
            return false;
        }
    }
    /**
     * get Previous Month
     * @param $dataRow
     * @return false|string
     */
    public function getPreviousMonth($dataRow)
    {
        if (isset($dataRow['import_month'])) {
            return date('Ym', strtotime('-1 months', strtotime($dataRow['import_month'].'01')));
        }
        return '';
    }

    /**
     * Validate data of csv file
     * @param $rowIndex
     * @param $rowData
     * @return bool
     */
    public function validateAllFields($rowIndex, $rowData)
    {
        if ($rowData['beginRow'] == self::TXT_FILE_ROW_START_CHARACTER) {
            $validationFinalResult = true;
            //validate increment id
            $validationFinalResult = $this->validateIncrementId($rowIndex, $rowData);
            $validationRules = [
                'required' => ['import_month','business_code','shipped_out_date','data_type','product_line_name'],
                'numeric' => ['row_total','unit_price'],
                'positive_number' => ['qty'],
                'maxlength' => ['import_month','data_type','business_code','increment_id','product_line_name'],
                'value_list' => ['data_type'],
                'datetime' => ['import_month','shipped_out_date']
            ];
            foreach ($validationRules as $rule => $fields) {
                foreach ($fields as $field) {
                    $validationField = $this->validateField(
                        $rule,
                        $rowData[$field],
                        $rowIndex,
                        $field
                    );
                    if (!$validationField) {
                        $validationFinalResult = false;
                    }
                }
            }
            return $validationFinalResult;
        } else {
            $this->helperData->writeToLog(
                __(
                    'Row[%1]: First character of row should be equal to [%2]',
                    $rowIndex,
                    self::TXT_FILE_ROW_START_CHARACTER
                )
            );
            return false;
        }
    }

    /**
     * Validate all fields
     *
     * @param $rule
     * @param $value
     * @param $rowIndex
     * @param $fieldName
     * @return bool
     */
    private function validateField($rule, $value, $rowIndex, $fieldName)
    {
        $maxlengthField = [
            'import_month'=>6,
            'data_type'=>2,
            'business_code'=>255,
            'increment_id'=>50,
            'product_line_name'=>255
        ];
        switch ($rule) {
            case 'numeric':
                $validationResult = $this->validateNumber($value, $rowIndex, $fieldName);
                break;
            case 'positive_number':
                $validationResult = $this->validatePositiveNumber(
                    $value,
                    $rowIndex,
                    $fieldName
                );
                break;
            case 'maxlength':
                $validationResult = $this->validateMaxlength(
                    $value,
                    $rowIndex,
                    $fieldName,
                    $maxlengthField[$fieldName]
                );
                break;
            case 'value_list':
                $validationResult = $this->validateDataType($value, $rowIndex, $fieldName);
                break;
            case 'datetime':
                $validationResult = $this->validateDatetime($value, $rowIndex, $fieldName);
                break;
            case 'required':
                $validationResult = $this->validateRequire($value, $rowIndex, $fieldName);
                break;
            default:
                $validationResult = true;
                break;
        }
        return $validationResult;
    }

    /**
     * @param $rowIndex
     * @param $dataRow
     * @param bool $isAdmin
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateIncrementId($rowIndex, $dataRow, $isAdmin = false)
    {
        if ($dataRow['data_type'] == DataType::DATA_TYPE_OPTION_RETURN) { //RMA
            if ($rma = $this->getRMAbyIncrementId($dataRow['increment_id'])) {
                if ($order = $this->getCustomerAndNicknameByOrder($rma->getData('order_id'))) {
                    return true;
                } else {
                    if ($isAdmin) {
                        return __('Order of RMA: #%1 does not exist', $dataRow['increment_id']);
                    } else {
                        $this->helperData->writeToLog(
                            __('Row[%1]: Order of this RMA ID #%2 does not exist', $rowIndex, $dataRow['increment_id'])
                        );
                        return false;
                    }
                }
            } else {
                //return error
                if ($isAdmin) {
                    return __('RMA ID #%1 does not exist', $dataRow['increment_id']);
                } else {
                    $this->helperData->writeToLog(
                        __('Row[%1]: RMA ID #%2 does not exist', $rowIndex, $dataRow['increment_id'])
                    );
                    return false;
                }
            }
        } else { //shipment
            if ($shipment = $this->getShipmentByIncrementId($dataRow['increment_id'])) {
                return true;
            } else {
                if ($isAdmin) {
                    return __('Shipment ID #%1 does not exist', $dataRow['increment_id']);
                } else {
                    $this->helperData->writeToLog(
                        __('Row[%1]: Shipment ID #%2 does not exist', $rowIndex, $dataRow['increment_id'])
                    );
                    return false;
                }
            }
        }
    }

    /**
     * @param $shipmentIncrementId
     * @return bool|\Magento\Framework\DataObject
     */
    private function getShipmentByIncrementId($shipmentIncrementId)
    {
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter('main_table.increment_id', $shipmentIncrementId);
        $shipmentCollection->join(
            'sales_order',
            'main_table.order_id = sales_order.entity_id',
            ['customer_id','created_at as order_created_date']
        );
        $shipmentCollection->join(
            'sales_order_address',
            'sales_order_address.parent_id  = sales_order.entity_id',
            ['riki_nickname']
        );
        $shipmentCollection->addFieldToFilter(
            'sales_order_address.address_type',
            \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING
        );
        $shipmentCollection->setPageSize(1)->setCurPage(1);
        if ($shipmentCollection->getSize()) {
            foreach ($shipmentCollection->getItems() as $item) {
                return $item;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $rmaIncrementId
     * @return bool|\Magento\Framework\DataObject
     */
    private function getRMAbyIncrementId($rmaIncrementId)
    {
        $rmaCollection = $this->rmaCollectionFactory->create();
        $rmaCollection->addFieldToFilter('main_table.increment_id', $rmaIncrementId);
        $rmaCollection->setPageSize(1)->setCurPage(1);
        if ($rmaCollection->getSize()) {
            foreach ($rmaCollection->getItems() as $item) {
                return $item;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $orderId
     * @return bool|\Magento\Framework\DataObject
     */
    private function getCustomerAndNicknameByOrder($orderId)
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $salesConnection = $this->connectionHelper->getSalesConnection();
        $orderAddressTable = $salesConnection->getTableName('sales_order_address');
        $orderCollection->addFieldToFilter('entity_id', $orderId);
        $orderCollection->join(
            $orderAddressTable,
            "$orderAddressTable.parent_id  = main_table.entity_id",
            ['riki_nickname']
        );
        $orderCollection->addFieldToFilter(
            'address_type',
            \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING
        );
        $orderCollection->setPageSize(1)->setCurPage(1);
        if ($orderCollection->getSize()) {
            foreach ($orderCollection->getItems() as $item) {
                return $item;
            }
        } else {
            return false;
        }
    }

    /**
     * @param array $dataRow
     * @return array
     */
    public function extractDataFromIncrementId(array $dataRow)
    {
        if ($dataRow['data_type'] == DataType::DATA_TYPE_OPTION_RETURN) { //RMA
            if ($rma = $this->getRMAbyIncrementId($dataRow['increment_id'])) {
                if ($order = $this->getCustomerAndNicknameByOrder($rma->getData('order_id'))) {
                    $dataRow['customer_id'] = $order->getData('customer_id');
                    $dataRow['riki_nickname'] = $order->getData('riki_nickname');
                    $dataRow['order_id'] = $order->getData('entity_id');
                    $dataRow['order_created_date'] = $order->getData('created_at');
                }
            }
        } else { //shipment
            if ($shipment = $this->getShipmentByIncrementId($dataRow['increment_id'])) {
                $dataRow['customer_id'] = $shipment->getData('customer_id');
                $dataRow['riki_nickname'] = $shipment->getData('riki_nickname');
                $dataRow['order_id'] = $shipment->getData('order_id');
                $dataRow['order_created_date'] = $shipment->getData('order_created_date');
            }
        }
        return $dataRow;
    }
}
