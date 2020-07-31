<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model;

class WishlistEditor
{
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory
     */
    protected $wishlistColFactory;

    /**
     * @var \Magento\MultipleWishlist\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistColFactory
     * @param \Magento\MultipleWishlist\Helper\Data $helper
     */
    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistColFactory,
        \Magento\MultipleWishlist\Helper\Data $helper
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->customerSession = $customerSession;
        $this->wishlistColFactory = $wishlistColFactory;
        $this->helper = $helper;
    }

    /**
     * Edit wishlist
     *
     * @param int $customerId
     * @param string $wishlistName
     * @param bool $visibility
     * @param int $wishlistId
     * @return \Magento\Wishlist\Model\Wishlist
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function edit($customerId, $wishlistName, $visibility = false, $wishlistId = null)
    {
        if (!$customerId) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Sign in to edit wish lists.'));
        }

        /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();

        if ($wishlistId) {
            $wishlist->load($wishlistId);
            if ($wishlist->getCustomerId() !== $this->customerSession->getCustomerId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The wish list is not assigned to your account and can\'t be edited.')
                );
            }
        } else {
            if (empty($wishlistName)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Provide the wish list name.'));
            }

            /** @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlistCollection */
            $wishlistCollection = $this->wishlistColFactory->create();
            $wishlistCollection->filterByCustomerId($customerId);
            $wishlistCollection->addFieldToFilter('name', $wishlistName);

            if ($wishlistCollection->getSize()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Wish list "%1" already exists.', $wishlistName)
                );
            }

            $limit = $this->helper->getWishlistLimit();
            if ($this->helper->isWishlistLimitReached($wishlistCollection)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Only %1 wish list(s) can be created.', $limit)
                );
            }

            $wishlist->setCustomerId($customerId);
            $wishlist->generateSharingCode();
        }

        $wishlist->setName($wishlistName)
            ->setVisibility($visibility)
            ->save();

        return $wishlist;
    }
}
