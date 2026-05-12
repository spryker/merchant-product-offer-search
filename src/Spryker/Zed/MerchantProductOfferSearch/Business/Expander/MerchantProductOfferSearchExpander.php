<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Business\Expander;

use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MerchantProductOfferConditionsTransfer;
use Generated\Shared\Transfer\MerchantProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\PageMapTransfer;
use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToMerchantProductOfferFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToStoreFacadeInterface;
use Spryker\Zed\ProductPageSearchExtension\Dependency\PageMapBuilderInterface;

class MerchantProductOfferSearchExpander implements MerchantProductOfferSearchExpanderInterface
{
    /**
     * @uses \Spryker\Shared\ProductOffer\ProductOfferConfig::STATUS_APPROVED
     *
     * @var string
     */
    protected const STATUS_APPROVED = 'approved';

    /**
     * @var \Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToMerchantProductOfferFacadeInterface
     */
    protected $merchantProductOfferFacade;

    /**
     * @var \Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToStoreFacadeInterface
     */
    protected $storeFacade;

    public function __construct(
        MerchantProductOfferSearchToMerchantProductOfferFacadeInterface $merchantProductOfferFacade,
        MerchantProductOfferSearchToStoreFacadeInterface $storeFacade
    ) {
        $this->merchantProductOfferFacade = $merchantProductOfferFacade;
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     */
    public function preloadProductOffers(array $productConcreteTransfers): void
    {
        $skus = array_values(array_filter(array_map(
            fn (ProductConcreteTransfer $transfer) => $transfer->getSku(),
            $productConcreteTransfers,
        )));

        if ($skus === []) {
            return;
        }

        $storeIds = $this->extractStoreIds($productConcreteTransfers);

        if ($storeIds === []) {
            return;
        }

        $criteria = (new MerchantProductOfferCriteriaTransfer())
            ->setMerchantProductOfferConditions(
                (new MerchantProductOfferConditionsTransfer())
                    ->setSkus($skus)
                    ->setStoreIds($storeIds),
            );

        $this->merchantProductOfferFacade->preloadProductOfferCollection($criteria);
    }

    /**
     * @param \Generated\Shared\Transfer\PageMapTransfer $pageMapTransfer
     * @param \Spryker\Zed\ProductPageSearchExtension\Dependency\PageMapBuilderInterface $pageMapBuilder
     * @param array<string, mixed> $productData
     * @param \Generated\Shared\Transfer\LocaleTransfer $localeTransfer
     *
     * @return \Generated\Shared\Transfer\PageMapTransfer
     */
    public function expandProductConcretePageMap(
        PageMapTransfer $pageMapTransfer,
        PageMapBuilderInterface $pageMapBuilder,
        array $productData,
        LocaleTransfer $localeTransfer
    ): PageMapTransfer {
        if (!$productData[ProductConcretePageSearchTransfer::SKU]) {
            return $pageMapTransfer;
        }

        $storeTransfer = $this->storeFacade->getStoreByName($productData[ProductConcretePageSearchTransfer::STORE]);

        $merchantProductOfferCriteriaTransfer = (new MerchantProductOfferCriteriaTransfer())
            ->addSku($productData[ProductConcretePageSearchTransfer::SKU])
            ->addIdStore($storeTransfer->getIdStoreOrFail());
        $productOfferCollectionTransfer = $this->merchantProductOfferFacade
            ->getProductOfferCollection($merchantProductOfferCriteriaTransfer);

        foreach ($productOfferCollectionTransfer->getProductOffers() as $productOfferTransfer) {
            if (!$productOfferTransfer->getIsActive() || $productOfferTransfer->getApprovalStatus() !== static::STATUS_APPROVED) {
                continue;
            }

            $merchantReference = $productOfferTransfer->getMerchantReferenceOrFail();
            $pageMapTransfer->addMerchantReference($merchantReference);
        }

        return $pageMapTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfers
     *
     * @return array<int>
     */
    protected function extractStoreIds(array $productConcreteTransfers): array
    {
        $storeIds = [];

        foreach ($productConcreteTransfers as $productConcreteTransfer) {
            foreach ($productConcreteTransfer->getStores() as $storeTransfer) {
                $idStore = $storeTransfer->getIdStore();

                if ($idStore === null) {
                    continue;
                }

                $storeIds[$idStore] = $idStore;
            }
        }

        return array_values($storeIds);
    }
}
