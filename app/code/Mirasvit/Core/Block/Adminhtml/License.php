<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-core
 * @version   1.2.31
 * @copyright Copyright (C) 2017 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Mirasvit\Core\Model\ModuleFactory;
use Mirasvit\Core\Model\LicenseFactory;

class License extends Template
{
    /**
     * @var ModuleFactory
     */
    private $moduleFactory;

    /**
     * @var LicenseFactory
     */
    private $licenseFactory;

    public function __construct(
        ModuleFactory $moduleFactory,
        LicenseFactory $licenseFactory,
        Template\Context $context
    ) {
        $this->moduleFactory = $moduleFactory;
        $this->licenseFactory = $licenseFactory;

        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getLinks()
    {
        $links = [];

        $module = $this->moduleFactory->create();
        foreach ($module->getInstalledModules() as $moduleName) {
            $moduleName = str_replace('Mirasvit_', '', $moduleName);

            $license = $this->licenseFactory->create();

            if ($license->load('\\' . $moduleName) === true) {
                continue;
            }

            if ($license->isNeedUpdate() && $license->getLicense()) {
                $links[$license->getLicense()] = $license->getRequestUrl();
            }
        }

        return $links;
    }
}