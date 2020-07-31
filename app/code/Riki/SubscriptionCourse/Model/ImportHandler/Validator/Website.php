<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class Website extends AbstractImportValidator
{
    protected $storeManager;

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_website']) {
            return false;
        }

        $subWebsiteIds = json_decode($value['subscription_course_website'], true);

        if ($subWebsiteIds) {
            $websiteIds = [];
            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                $websiteIds[] = $website->getId();
            }
        } else {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_JSON_FORMAT
                        ),
                        $value['subscription_course_website']
                    )
                ]
            );
            return false;
        }
        $notExistWebsites = array_diff($subWebsiteIds, $websiteIds);
        if ($notExistWebsites) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_WEBSITE_NOT_FOUND
                        ),
                        implode(',', $notExistWebsites)
                    )
                ]
            );
            return false;
        } else {
            return true;
        }
    }
}
