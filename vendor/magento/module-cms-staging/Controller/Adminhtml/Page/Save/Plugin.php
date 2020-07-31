<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Controller\Adminhtml\Page\Save;

use Magento\Cms\Controller\Adminhtml\Page\Save;
use Psr\Log\LoggerInterface;

class Plugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param Save $subject
     * @return void
     */
    public function beforeExecute(Save $subject)
    {
        try {
            $customTheme = $subject->getRequest()->getPostValue('custom_theme');
            if ($this->isValidCustomTheme($customTheme)) {
                $date = new \DateTime(null, new \DateTimeZone('UTC'));
                $subject->getRequest()->setPostValue('custom_theme_from', $date->format('m/d/Y'));
            } else {
                $subject->getRequest()->setPostValue('custom_theme_from', null);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Check if custom theme identifier exists and valid
     *
     * @param mixed $customTheme
     * @return bool
     */
    private function isValidCustomTheme($customTheme)
    {
        if ($customTheme !== null && $customTheme > 0) {
            return true;
        }
        return false;
    }
}
