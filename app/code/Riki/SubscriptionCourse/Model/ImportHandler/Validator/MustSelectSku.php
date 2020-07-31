<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class MustSelectSku extends AbstractImportValidator
{
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * MustSelectSku constructor.
     *
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     */
    public function __construct(\Magento\Catalog\Model\CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['must_select_sku']) {
            return true;
        }

        $valid = preg_match('/[0-9]\:[0-9]$/', $value['must_select_sku']);

        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                        ),
                        'must_select_sku'
                    )
                ]
            );

            return false;
        }

        $categoryIdQty = explode(':', $value['must_select_sku']);

        if (!empty($categoryIdQty[0])) {
            try {
                $category = $this->categoryRepository->get($categoryIdQty[0]);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_INVALID_CATEGORY_ID
                            ),
                            $categoryIdQty[0]
                        )
                    ]
                );

                return false;
            }
        }

        return true;
    }
}
