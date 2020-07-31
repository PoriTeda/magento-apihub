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
 * @version   1.2.89
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Model\Config;
use Mirasvit\Core\Model\Module;

class CompileLessObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private $viewSourceBasePath;

    private $module;

    private $moduleReader;

    private $config;

    private $storeManager;

    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        Module $module,
        Reader $moduleReader
    ) {
        $this->config       = $config;
        $this->storeManager = $storeManager;
        $this->module       = $module;
        $this->moduleReader = $moduleReader;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $layout = $observer->getData('layout');

        if ($this->config->isLessCompilationEnabled()) {
            /** @var \Magento\Framework\View\Page\Config\Structure $pageConfig */
            $pageConfig = $layout->getReaderContext()->getPageConfigStructure();
            $this->prepareAssetData();

            if ($this->isPreprocessed()) {
                $pageConfig->addAssets(
                    'Mirasvit_Core::css/source/include_all_modules.css',
                    [
                        'content_type' => 'css',
                        'src'          => 'Mirasvit_Core::css/source/include_all_modules.css',
                    ]
                );
            }
        }
    }

    private function isPreprocessed()
    {
        $this->viewSourceBasePath = $this->moduleReader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Mirasvit_Core') . '/frontend/web/css/source/';

        if (!file_exists($this->viewSourceBasePath . 'processedModules.json')) {
            return false;
        }

        if (!empty(file_get_contents($this->viewSourceBasePath . 'processedModules.json'))) {
            $processedModules = json_decode(file_get_contents($this->viewSourceBasePath . 'processedModules.json'), true);
        } else {
            return false;
        }

        foreach ($processedModules as $name => $version) {
            if ($this->module->load($name)->getInstalledVersion() != $version) {
                return false;
            }
        }

        $processedModulesDiff = array_diff(array_column($processedModules, 0), $this->module->getInstalledModules());
        $processedData        = file_exists($this->viewSourceBasePath . 'include_all_modules.less');

        if (!$processedData || count($processedModulesDiff) > 0) {
            return false;
        }

        return true;
    }

    private function prepareAssetData()
    {
        if (!$this->isPreprocessed()) {
            $modulesToImport = [];

            $filesToImport = [];

            foreach ($this->module->getInstalledModules() as $name) {
                $modulesToImport[$name] = $this->module->load($name)->getInstalledVersion();
                $moduleViewSourcePath   = $this->moduleReader->getModuleDir(Dir::MODULE_VIEW_DIR, $name) . '/frontend/web/css/source/';

                if (file_exists($moduleViewSourcePath . '_module.less')) {
                    $filesToImport[] = '@import "' . $name . '::css/source/_module.less"';
                }
            }

            if (!empty($filesToImport)) {
                @file_put_contents($this->viewSourceBasePath . 'processedModules.json', json_encode($modulesToImport));

                $import = file_get_contents($this->viewSourceBasePath .'_utilities.less'). "\n" . implode(';' . "\n", $filesToImport) . ';' ;
                @file_put_contents($this->viewSourceBasePath . 'include_all_modules.less', $import);
            }
        }
    }
}
