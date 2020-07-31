<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class Category extends AbstractImportValidator
{

    protected $categoryRepository;

    public function __construct(
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_category']) {
            return false;
        }

        $categoryTypes = json_decode($value['subscription_course_category'], true);

        $isValid = true;

        if (!in_array('main_categories', array_keys($categoryTypes)) || !$categoryTypes['main_categories']) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_MAIN_CATEGORY_NOT_FOUND
                        ),
                        'main_categories'
                    )
                ]
            );
            $isValid = false;
        }

        $categoryIdsNotFound = [];
        foreach ($categoryTypes as $type => $categoryIds) {
            foreach ($categoryIds as $categoryId) {
                try {
                    $this->categoryRepository->get($categoryId);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    $categoryIdsNotFound[] = $categoryId;
                }
            }
        }
        if ($categoryIdsNotFound) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_CATEGORY_ID
                        ),
                        implode(',', $categoryIdsNotFound)
                    )
                ]
            );

            $isValid = false;
        }

        return $isValid;
    }
}
