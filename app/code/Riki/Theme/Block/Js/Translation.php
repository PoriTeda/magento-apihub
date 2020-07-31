<?php

namespace Riki\Theme\Block\Js;

use Magento\Framework\View\Element\Template;
use Magento\Translation\Model\Js;
use Magento\Translation\Model\Js\Config;
use Magento\Framework\App\Filesystem\DirectoryList;

class Translation extends \Magento\Translation\Block\Js
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $resolver;

    /**
     * Translation constructor.
     * @param Template\Context $context
     * @param Config $config
     * @param \Magento\Translation\Model\FileManager $fileManager
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        \Magento\Translation\Model\FileManager $fileManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \Magento\Framework\Locale\Resolver $resolver,
        array $data = []
    ) {
        $this->assetRepo = $context->getAssetRepository();
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->resolver = $resolver;
        $websiteId = $context->getStoreManager()->getWebsite()->getId();
        $language = $resolver->getLocale();
        $areaCode = $context->getAppState()->getAreaCode();
        $cacheKey = self::class . '_' . 'riki_translation_'.$areaCode.'_' . $websiteId . '_' . $language;
        $this->addData([
            'cache_lifetime' => 86400, // Cache TTL: 1 Day
            'cache_key' => $cacheKey
        ]);
        parent::__construct($context, $config, $fileManager, $data);
    }

    /**
     * @return string
     */
    protected function getTranslationFileFullPath()
    {
        return $this->directoryList->getPath(DirectoryList::STATIC_VIEW) .
            \DIRECTORY_SEPARATOR .
            $this->assetRepo->getStaticViewFileContext()->getPath() .
            \DIRECTORY_SEPARATOR .
            Config::DICTIONARY_FILE_NAME;
    }

    /**
     * @return null|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getTranslationFromFile()
    {
        $data = "{}";
        $translationFilePath = $this->getTranslationFileFullPath();
        if ($this->driverFile->isExists($translationFilePath)) {
            $data = $this->driverFile->fileGetContents($translationFilePath);
        }
        return $data;
    }
}
