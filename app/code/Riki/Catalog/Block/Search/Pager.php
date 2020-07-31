<?php
namespace Riki\Catalog\Block\Search;

class Pager extends \Magento\Theme\Block\Html\Pager
{
    protected $_collectionSize;

    public function setCollectionSize($size)
    {
        $this->_collectionSize = $size;
        return $this;
    }

    public function getCollectionSize()
    {
        return $this->_collectionSize;
    }

    public function getLastPageNum()
    {
        $collectionSize = (int)$this->getCollectionSize();
        if (0 === $collectionSize) {
            return 1;
        } else {
            $limit = (int)$this->getLimit();
            if ($limit) {
                return ceil($collectionSize / $this->getLimit());
            }
        }
        return 1;
    }

    /**
     * @return array
     */
    public function getPages()
    {
        $collection = $this->getCollection();

        $lastPageNumber = $this->getLastPageNum();

        if ($lastPageNumber <= $this->_displayPages) {
            return range(1, $lastPageNumber);
        } else {
            $half = ceil($this->_displayPages / 2);
            if ($collection->getCurPage() >= $half &&
                $collection->getCurPage() <= $lastPageNumber - $half
            ) {
                $start = $collection->getCurPage() - $half + 1;
                $finish = $start + $this->_displayPages - 1;
            } elseif ($collection->getCurPage() < $half) {
                $start = 1;
                $finish = $this->_displayPages;
            } elseif ($collection->getCurPage() > $lastPageNumber - $half) {
                $finish = $lastPageNumber;
                $start = $finish - $this->_displayPages + 1;
            }
            return range($start, $finish);
        }
    }

    /**
     * Initialize frame data, such as frame start, frame start etc.
     *
     * @return $this
     */
    protected function _initFrame()
    {
        if (!$this->isFrameInitialized()) {
            $start = 0;
            $end = 0;

            $collection = $this->getCollection();

            $lastPageNumber = $this->getLastPageNum();

            if ($lastPageNumber <= $this->getFrameLength()) {
                $start = 1;
                $end = $lastPageNumber;
            } else {
                $half = ceil($this->getFrameLength() / 2);
                if ($collection->getCurPage() >= $half &&
                    $collection->getCurPage() <= $lastPageNumber - $half
                ) {
                    $start = $collection->getCurPage() - $half + 1;
                    $end = $start + $this->getFrameLength() - 1;
                } elseif ($collection->getCurPage() < $half) {
                    $start = 1;
                    $end = $this->getFrameLength();
                } elseif ($collection->getCurPage() > $lastPageNumber - $half) {
                    $end = $lastPageNumber;
                    $start = $end - $this->getFrameLength() + 1;
                }
            }
            $this->_frameStart = $start;
            $this->_frameEnd = $end;

            $this->_setFrameInitialized(true);
        }

        return $this;
    }
}
