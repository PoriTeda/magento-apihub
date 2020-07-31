<?php
namespace Riki\Framework\Helper;

class Sftp extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var string
     */
    protected $defaultDir = '';

    /**
     * @var $sftp
     */
    protected $sftp;

    /**
     * Sftp constructor.
     *
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\App\Helper\Context $context
    ){
        $this->sftp = $sftp;
        parent::__construct($context);
    }

    /**
     * Get default dir
     *
     * @return string
     */
    public function getDefaultDir()
    {
        return $this->defaultDir;
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param array $args
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function open(array $args = [])
    {
        try {
            $this->sftp->open($args);
            $this->defaultDir = $this->pwd();
        } catch (\Exception $e) {
            throw  $e;
        }
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @return void
     */
    public function close()
    {
        $this->sftp->close();
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $dir
     * @param int $mode
     * @param bool $recursive
     *
     * @return bool
     */
    public function mkdir($dir, $mode = 0777, $recursive = true)
    {
        $currentDir = $this->pwd();
        $sep = $this->dirsep();
        $tmpDir = $dir;
        $checkDir = '';
        do {
            $pos = strpos($tmpDir, $sep);
            $part = substr($tmpDir, 0, $pos + strlen($sep));
            if (!$this->isDirExist($checkDir . $part)) {
                $this->cd(rtrim($checkDir, $sep));
                break;
            }
            $checkDir .= $part;
            $tmpDir = substr($tmpDir, strlen($part));
        } while ($tmpDir);

        $result = $this->sftp->mkdir($sep . $tmpDir, $mode, $recursive);

        $this->cd($currentDir);

        return $result;
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $dir
     * @param bool $recursive
     *
     * @return bool
     */
    public function rmdir($dir, $recursive = false)
    {
        return $this->sftp->rmdir($dir, $recursive);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @return mixed
     */
    public function pwd()
    {
        return $this->sftp->pwd();
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $dir
     *
     * @return bool
     */
    public function cd($dir)
    {
        return $this->sftp->cd($dir);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $filename
     * @param null $destination
     *
     * @return mixed
     */
    public function read($filename, $destination = null)
    {
        return $this->sftp->read($filename, $destination);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $filename
     * @param $source
     * @param null $mode
     *
     * @return bool
     */
    public function write($filename, $source, $mode = null)
    {
        return $this->sftp->write($filename, $source, $mode);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $filename
     *
     * @return bool
     */
    public function rm($filename)
    {
        return $this->sftp->rm($filename);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $source
     * @param $destination
     *
     * @return bool
     */
    public function mv($source, $destination)
    {
        return $this->sftp->mv($source, $destination);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param $filename
     * @param $mode
     *
     * @return mixed
     */
    public function chmod($filename, $mode)
    {
        return $this->sftp->chmod($filename, $mode);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @param null $grep
     *
     * @return array
     */
    public function ls($grep = null)
    {
        return $this->sftp->ls($grep);
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @return mixed
     */
    public function rawls()
    {
        return $this->sftp->rawls();
    }

    /**
     * @see \Magento\Framework\Filesystem\Io\Sftp
     *
     * @return string
     */
    public function dirSep()
    {
        return $this->sftp->dirsep();
    }


    /**
     * Check a dir is exist
     *
     * @param string $dir
     *
     * @return bool
     */
    public function isDirExist($dir)
    {
        $currentDir = $this->sftp->pwd();
        if (!$this->sftp->cd($dir)) {
            return false;
        }

        $this->sftp->cd($currentDir);

        return true;
    }

    /**
     * Return list files match pattern
     *
     * @param $pattern
     * @return array
     */
    public function filter($pattern)
    {
        return preg_grep('/' . $pattern . '/', array_keys($this->sftp->rawls()));
    }
}