<?php

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class MultiFileFactory extends \Magento\Framework\App\Response\Http\FileFactory
{
    /* @var \Magento\Framework\App\Filesystem\DirectoryList */
    protected $directoryList;

    /* @var \Magento\Framework\Filesystem\Driver\File */
    protected $fileDirectory;

    /* @var \Magento\Framework\Filesystem\Io\File */
    protected $fileSystemIo;

    public function __construct(
        \Magento\Framework\Filesystem\Io\File $fileSystemIo,
        \Magento\Framework\Filesystem\Driver\File $fileDirectory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\Filesystem $filesystem
    )
    {
        parent::__construct($response, $filesystem);
        $this->directoryList = $directoryList;
        $this->fileDirectory = $fileDirectory;
        $this->fileSystemIo = $fileSystemIo;
    }

    public function createMultiFile(
        $arrContent,
        $baseDir = DirectoryList::ROOT,
        $sourcePathToZip
    ){
        $dir = $this->_filesystem->getDirectoryWrite($baseDir);

        $file = null;
        $name = md5(microtime());
        $zipFile = 'export/' . 'subscription-course' . $name . '.tar';
        $zip = new \ZipArchive();
        $zip->open($this->directoryList->getPath($baseDir).'/'.$zipFile, \ZipArchive::CREATE);
        foreach ($arrContent as $content) {
            if (!isset($content['type']) || !isset($content['value'])) {
                throw new \InvalidArgumentException("Invalid arguments. Keys 'type' and 'value' are required.");
            }
            if ($content['type'] == 'filename') {
                $file = $content['value'];
                if (!$dir->isFile($file)) {
                    throw new LocalizedException(__('File not found.'));
                }
            }
            $fileInfo = $this->fileSystemIo->getPathInfo($this->directoryList->getPath($baseDir).'/'.$content['value']);
            if (array_key_exists('basename', $fileInfo)) {
                $zip->addFromString($fileInfo['basename'],
                    $this->fileDirectory->fileGetContents($this->directoryList->getPath($baseDir) . '/' . $content['value']));
            }
        }
        $zip->close();
        return [
            'type' => 'filename',
            'value' => $zipFile,
            'rm' => true  // can delete file after use
        ];
    }
}