<?php
/**
 * @author    Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package   Amasty_Smtp
 */

namespace Amasty\Smtp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;

class Data extends AbstractHelper
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context           $context
     * @param \Magento\Store\Model\StoreManagerInterface      $storeManager
     * @param \Magento\Framework\App\State                    $appState
     * @param Registry                                        $registry
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File       $file
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $appState,
        Registry $registry,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        parent::__construct($context);

        $this->registry     = $registry;
        $this->storeManager = $storeManager;
        $this->appState     = $appState;
        if (defined('DS') === false) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        $this->directoryList = $directoryList;
        $this->file          = $file;
    }

    public function getCurrentStore()
    {
        $store = $this->storeManager->getStore();

        if ($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            /** @var \Magento\Sales\Model\Order $order */
            if ($order = $this->registry->registry('current_order')) {
                return $order->getStoreId();
            }

            return 0;
        }

        return $store->getId();
    }

    /**
     * @param $dirFolder
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function createFileLocal($dirFolder)
    {
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if (trim($dirFolder, -1) == DS) {
            $dirFolder = str_replace(DS, '', $dirFolder);
        }
        $createFileLocal = $baseDir . DS . $dirFolder;
        if (!$this->file->isDirectory($createFileLocal)) {
            if (!$this->file->createDirectory($createFileLocal)) {
                return false;
            }
        }

        if (!$this->file->isWritable($createFileLocal)) {
            return false;
        }
        return $createFileLocal;
    }
}
