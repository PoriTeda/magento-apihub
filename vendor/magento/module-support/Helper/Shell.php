<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Helper;

use \Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Helper for work with shell
 */
class Shell extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * File that contains paths to shell commands
     */
    const PATHS_FILE        = 'Paths.php';

    /**#@+
     * Shell commands
     */
    const UTILITY_NICE      = 'nice';
    const UTILITY_TAR       = 'tar';
    const UTILITY_MYSQLDUMP = 'mysqldump';
    const UTILITY_GZIP      = 'gzip';
    const UTILITY_LSOF      = 'lsof';
    const UTILITY_PHP       = 'php';
    const UTILITY_SED       = 'sed';
    const UTILITY_BASH      = 'bash';
    const UTILITY_MYSQL     = 'mysql';
    /**#@-*/

    const XML_OUTPUT_PATH   = 'support/output_path';

    /**
     * @var \Magento\Framework\ShellInterface
     */
    protected $shell;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @var array
     */
    protected $utilities = [];

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var string
     */
    protected $absoluteOutputPath;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ShellInterface $shell
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ShellInterface $shell,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->shell = $shell;
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
    }

    /**
     * Wrapper for execute
     *
     * @param string $command
     * @param array $arguments
     * @return string
     */
    public function execute($command, array $arguments = [])
    {
        return $this->shell->execute($command, $arguments);
    }

    /**
     * Get paths file path
     *
     * @return string
     */
    public function getPathsFileName()
    {
        return $this->getOutputPath() . '/' . self::PATHS_FILE;
    }

    /**
     * Collect paths for required console utilities
     *
     * @param bool $force
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function initPaths($force = false)
    {
        if (!empty($this->utilities)) {
            return;
        }

        $pathsFile = $this->getPathsFileName();
        if (!$force && $this->directory->isExist($pathsFile)) {
            $this->utilities = include($pathsFile);
            return;
        }

        $list = [
            self::UTILITY_GZIP,
            self::UTILITY_LSOF,
            self::UTILITY_MYSQLDUMP,
            self::UTILITY_NICE,
            self::UTILITY_PHP,
            self::UTILITY_TAR,
            self::UTILITY_SED,
            self::UTILITY_BASH,
            self::UTILITY_MYSQL
        ];
        foreach ($list as $name) {
            try {
                $this->utilities[$name] = $this->execute('which ' . $name);
            } catch (LocalizedException $e) {
                throw new \Magento\Framework\Exception\NotFoundException(
                    __('The "%1" utility wasn\'t found. Verify the utility and try again.', $name)
                );
            }
        }
    }

    /**
     * Get utility path by utility name
     *
     * @param string $name
     * @return mixed
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getUtility($name)
    {
        $this->initPaths();

        if (!isset($this->utilities[$name])) {
            throw new \Magento\Framework\Exception\NotFoundException(
                __('The "%1" utility is unknown. Verify the utility and try again.', $name)
            );
        }

        return $this->utilities[$name];
    }

    /**
     * @return array
     */
    public function getUtilities()
    {
        return $this->utilities;
    }

    /**
     * Get output path
     *
     * @return string
     */
    public function getOutputPath()
    {
        if (null === $this->outputPath) {
            $this->outputPath = $this->scopeConfig->getValue(self::XML_OUTPUT_PATH);

            if (!$this->directory->isExist($this->outputPath)) {
                $this->directory->create($this->outputPath);
            }
        }

        return $this->outputPath;
    }

    /**
     * Get absolute output path
     *
     * @return string
     */
    public function getAbsoluteOutputPath()
    {
        if (null === $this->absoluteOutputPath) {
            $relativePath = $this->getOutputPath();

            $this->absoluteOutputPath = $this->directory->getAbsolutePath($relativePath);
        }

        return $this->absoluteOutputPath;
    }

    /**
     * Get Item Path
     *
     * @param string $itemName
     * @return string
     */
    public function getFilePath($itemName)
    {
        return $this->getOutputPath() . $itemName;
    }

    /**
     * Get absolute item path
     *
     * @param string $itemName
     * @return string
     */
    public function getAbsoluteFilePath($itemName)
    {
        return $this->getAbsoluteOutputPath() . $itemName;
    }

    /**
     * Get file size
     *
     * @param string $itemName
     * @return int
     */
    public function getFileSize($itemName)
    {
        return filesize($this->getAbsoluteFilePath($itemName));
    }

    /**
     * Check file is locked
     *
     * @param string $filePath
     * @return bool
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function isFileLocked($filePath)
    {
        return (bool)exec($this->getUtility(self::UTILITY_LSOF) . ' ' . $this->directory->getAbsolutePath($filePath));
    }

    /**
     * Check if php can run bash script
     *
     * @return bool
     */
    public function isExecEnabled()
    {
        $disabledFunctions = explode(',', ini_get('disable_functions'));

        return function_exists('exec') && !in_array('exec', $disabledFunctions);
    }

    /**
     * Set working directory on the Magento root directory
     *
     * @return void
     * @throws LocalizedException
     */
    public function setRootWorkingDirectory()
    {
        $magentoRootPath = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT)->getAbsolutePath();
        if (!chdir($magentoRootPath)) {
            throw new LocalizedException(__("The root category can't be set. Verify the category and try again."));
        }
    }
}
