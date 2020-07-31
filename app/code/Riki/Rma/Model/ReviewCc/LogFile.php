<?php
namespace Riki\Rma\Model\ReviewCc;

use Magento\Framework\App\Filesystem\DirectoryList;

class LogFile extends \Magento\Framework\DataObject
{
    /** @var \Magento\Framework\Stdlib\DateTime\DateTime  */
    protected $dateTime;

    /** @var DirectoryList  */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;

    /**
     * LogFile constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        array $data = []
    )
    {
        parent::__construct($data);

        $this->dateTime = $dateTime;
        $this->directoryList = $directoryList;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->varDirectory->create($this->getBaseLogPath());
    }

    /**
     * @return string
     */
    public function getBaseLogPath()
    {
        return 'rma/review_cc';
    }

    /**
     * @param \Riki\Rma\Model\ReviewCc $reviewCc
     * @return $this
     */
    public function setReviewCc(\Riki\Rma\Model\ReviewCc $reviewCc)
    {
        return $this->setData('review_cc', $reviewCc);
    }

    /**
     * @return string
     */
    public function getHtmlFileName()
    {
        return 'review_by_cc_' . $this->dateTime->date('YmdHis', $this->getReviewCc()->getExecutedFrom()) . '.log';
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        /** @var \Riki\Rma\Model\ReviewCc $reviewCc */
        $reviewCc = $this->getReviewCc();

        return 'review_cc_' . $reviewCc->getId() . '.log';
    }

    /**
     * @return string
     */
    public function getErrorFilePath()
    {
        /** @var \Riki\Rma\Model\ReviewCc $reviewCc */
        $reviewCc = $this->getReviewCc();

        return $this->varDirectory->getAbsolutePath($this->getBaseLogPath() . '/review_by_cc_error_' . $reviewCc->getId() . '.log');
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        $filename = $this->getFileName();

        return $this->varDirectory->getAbsolutePath($this->getBaseLogPath() . '/' . $filename);
    }

    /**
     * @return bool
     */
    public function fileExists()
    {
        $filename = $this->getFileName();

        return $this->varDirectory->isExist($this->getBaseLogPath() . '/' . $filename);
    }

    /**
     * use for download
     *
     * @return array
     */
    public function getFileContent()
    {
        $filename = $this->getFileName();

        return [
            'type' => 'filename',
            'value' => $this->getBaseLogPath() . '/' . $filename,
            'rm' => false  // not delete file after use
        ];
    }
}