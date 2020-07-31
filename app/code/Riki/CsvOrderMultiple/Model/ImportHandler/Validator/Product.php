<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use \Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class Product extends AbstractImportValidator
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    protected $productRepository;

    /** @var \Riki\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory  */
    protected $giftWrappingCollectionFactory;

    /**
     * @var array
     */
    protected $deliveryTypeProduct = [];

    /**
     * @var array
     */
    protected $listProductAvailability = [];

    /**
     * Product constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $giftWrappingCollectionFactory
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $giftWrappingCollectionFactory
    ) {
        $this->productRepository = $productRepository;
        $this->giftWrappingCollectionFactory = $giftWrappingCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $result = true;
        $this->listProductAvailability = [];
        $this->deliveryTypeProduct = [];

        if (!empty($value['products'])) {
            $productsData = explode(';', $value['products']);
            foreach ($productsData as $productData) {
                $productData = explode(':', $productData);
                $result = $this->isValidProduct($result, $productData);
            }
        }

        $this->validator->setIsListProductValid($result);
        $this->validator->setDeliveryTypeListProductImport($this->deliveryTypeProduct);
        $this->validator->setListProductAvailability($this->listProductAvailability);

        return $result;
    }

    /**
     * @param $sku
     * @param $qty
     * @param null $giftCode
     * @return bool
     */
    public function validateProduct($sku, $qty, $giftCode = null)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            $this->_addMessages(
                [
                    $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_SKU_NOT_FOUND)
                ]
            );
            return false;
        }

        //check product not active
        if ($product->getStatus() == ProductStatus::STATUS_DISABLED) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_SKU_DISABLE),
                        $sku
                    )
                ]
            );
            return false;
        }

        if ((!is_numeric($qty) || $qty <= 0)) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE),
                        'qty',
                        'decimal'
                    )
                ]
            );
            return false;
        }

        if ($giftCode) {
            $valid = false;

            $gWsSelected = $product->getData('gift_wrapping');

            if ($gWsSelected) {
                $gWsSelected = explode(',', $gWsSelected);

                /** @var \Riki\GiftWrapping\Model\ResourceModel\Wrapping\Collection $gWCollection */
                $gWCollection = $this->giftWrappingCollectionFactory->create();
                $valid = $gWCollection->addFieldToFilter('wrapping_id', ['in'   =>  $gWsSelected])
                ->addFieldToFilter('gift_code', $giftCode)
                ->getSize();
            }

            if (!$valid) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_INVALID_GIFT_WRAPPING_CODE
                            ),
                            $giftCode,
                            $product->getSku()
                        )
                    ]
                );

                return false;
            }
        }

        /**
         * Delivery of product
         */
        if ($product) {
            $this->deliveryTypeProduct[$product->getId()] = $product->getDeliveryType();
            $this->listProductAvailability[$product->getId()] = [
                'sku' => $product->getSku(),
                'product_id' => $product->getId(),
                'qty' =>$qty
            ];
        }

        return true;
    }

    /**
     * @param $result
     * @param $productData
     * @return bool
     */
    public function isValidProduct($result, $productData)
    {
        $count = count($productData);
        switch ($count) {
            case 2:
            case 3:
                $result = $this->validateProduct(
                    $productData[0],
                    $productData[1],
                    isset($productData[2])? $productData[2] : null
                );
                break;
            default:
                $result = false;
                $this->_addMessages(
                    [
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_SKU_NOT_FOUND)
                    ]
                );
        }
        return $result;
    }
}
