<?php
namespace Riki\Base\Model;

/**
 * Class BookmarkData
 * @package Riki\Base\Model
 */
class BookmarkData
{
    /**
     * @var \Magento\Ui\Api\BookmarkRepositoryInterface
     */
    protected $bookmarkRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriterialBuilder;
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * BookmarkData constructor.
     *
     * @param \Magento\Ui\Api\BookmarkRepositoryInterface $bookmarkRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Json\EncoderInterface $encoder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     */
    public function __construct(
        \Magento\Ui\Api\BookmarkRepositoryInterface $bookmarkRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\UrlInterface $backendUrl
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->searchCriterialBuilder = $searchCriteriaBuilder;
        $this->jsonEncoder = $encoder;
        $this->logger = $logger;
        $this->backendUrl = $backendUrl;
    }

    /**
     *
     */
    public function refactorData()
    {
        $criteria = $this->searchCriterialBuilder
            ->addFilter('identifier', 'current')
            ->addFilter('current', 0)
            ->addFilter('title', true, 'null')
            ->create();
        $bookmarkCollection = $this->bookmarkRepository->getList($criteria);
        if ($bookmarkCollection->getTotalCount()) {
            foreach ($bookmarkCollection->getItems() as $bookmark) {
                $this->fixSearchKeyword($bookmark);
            }
        }
    }

    /**
     * trim keyword if it's too long
     * @param \Magento\Ui\Model\Bookmark $bookmark
     */
    private function fixSearchKeyword(\Magento\Ui\Model\Bookmark $bookmark)
    {
        try {
            $this->bookmarkRepository->save($bookmark);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
        }
    }
}
