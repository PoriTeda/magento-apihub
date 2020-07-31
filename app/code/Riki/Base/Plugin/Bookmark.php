<?php
namespace Riki\Base\Plugin;

class Bookmark
{
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * Bookmark constructor.
     * @param \Magento\Framework\Json\EncoderInterface $encoder
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     */
    public function __construct(
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Backend\Model\UrlInterface $backendUrl
    ) {
        $this->jsonEncoder = $encoder;
        $this->backendUrl = $backendUrl;
    }
    /**
     * Limit search keyword if it too long
     *
     * @param \Magento\Ui\Model\Bookmark $subject
     * @return array
     */
    public function beforeBeforeSave(
        \Magento\Ui\Model\Bookmark $subject
    ) {
        $maxLengthUri = 2083;
        $searchUri = $this->backendUrl->getUrl('mui/index/render').'/?namespace='.$subject->getNamespace();
        $configData = $subject->getConfig();
        if (isset($configData['current']['search']['value'])) {
            $keywordLength = mb_strlen($configData['current']['search']['value']);
            if ($keywordLength + mb_strlen($searchUri) > $maxLengthUri) {
                $needKeywordLenth = $maxLengthUri - mb_strlen($searchUri) - 1;
                $newKeyword = mb_substr($configData['current']['search']['value'], 0, $needKeywordLenth);
                $configData['current']['search']['value'] = $newKeyword;
                $subject->setConfig($this->jsonEncoder->encode($configData));
            }
        }
        return [];
    }
}
