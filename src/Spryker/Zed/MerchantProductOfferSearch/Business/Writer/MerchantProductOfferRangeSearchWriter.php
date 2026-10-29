<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Business\Writer;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Shared\MerchantProductOfferSearch\MerchantProductOfferSearchConfig;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToEventFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Dependency\Facade\MerchantProductOfferSearchToProductPageSearchFacadeInterface;
use Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchRepositoryInterface;

class MerchantProductOfferRangeSearchWriter implements MerchantProductOfferRangeSearchWriterInterface
{
    // One run of the range events can hold queue chunk size x range size offers; a regular concrete publish run publishes one queue chunk.
    protected const int PRODUCT_CONCRETE_PUBLISH_BATCH_SIZE = 500;

    protected const int PRODUCT_ABSTRACT_EVENT_BATCH_SIZE = 1000;

    protected const int PRODUCT_OFFER_ID_RANGES_PER_QUERY = 100;

    public function __construct(
        protected MerchantProductOfferSearchRepositoryInterface $repository,
        protected MerchantProductOfferSearchToProductPageSearchFacadeInterface $productPageSearchFacade,
        protected MerchantProductOfferSearchToEventFacadeInterface $eventFacade
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     */
    public function writeCollectionByRangeEvents(array $rangeEventEntityTransfers): void
    {
        $productConcreteIds = [];
        $productAbstractIds = [];

        foreach ($this->getProductConcretesByRangeEvents($rangeEventEntityTransfers) as $productConcreteTransfer) {
            $productConcreteIds[$productConcreteTransfer->getIdProductConcreteOrFail()] = $productConcreteTransfer->getIdProductConcreteOrFail();
            $productAbstractIds[$productConcreteTransfer->getFkProductAbstractOrFail()] = $productConcreteTransfer->getFkProductAbstractOrFail();
        }

        if (!$productConcreteIds) {
            return;
        }

        foreach (array_chunk(array_values($productConcreteIds), max(1, static::PRODUCT_CONCRETE_PUBLISH_BATCH_SIZE)) as $productConcreteIdsBatch) {
            $this->productPageSearchFacade->publishProductConcretes($productConcreteIdsBatch);
        }

        foreach (array_chunk(array_values($productAbstractIds), max(1, static::PRODUCT_ABSTRACT_EVENT_BATCH_SIZE)) as $productAbstractIdsBatch) {
            $this->eventFacade->triggerBulk(
                MerchantProductOfferSearchConfig::PRODUCT_ABSTRACT_SEARCH_PUBLISH,
                $this->createProductAbstractEventEntityTransfers($productAbstractIdsBatch),
            );
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     *
     * @return list<\Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    protected function getProductConcretesByRangeEvents(array $rangeEventEntityTransfers): array
    {
        $productConcreteTransfers = [];

        foreach ($this->getProductOfferIdRangesGroupedByMerchantReference($rangeEventEntityTransfers) as $merchantReference => $productOfferIdRanges) {
            foreach (array_chunk($productOfferIdRanges, max(1, static::PRODUCT_OFFER_ID_RANGES_PER_QUERY)) as $productOfferIdRangesChunk) {
                $productConcreteTransfers[] = $this->repository->getProductConcretesByMerchantReferenceAndProductOfferIdRanges(
                    (string)$merchantReference,
                    $productOfferIdRangesChunk,
                );
            }
        }

        return array_merge(...$productConcreteTransfers);
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $rangeEventEntityTransfers
     *
     * @return array<string, list<array{int, int}>>
     */
    protected function getProductOfferIdRangesGroupedByMerchantReference(array $rangeEventEntityTransfers): array
    {
        $productOfferIdRanges = [];

        foreach ($rangeEventEntityTransfers as $rangeEventEntityTransfer) {
            $additionalValues = $rangeEventEntityTransfer->getAdditionalValues();
            $productOfferIdRanges[(string)$additionalValues[MerchantProductOfferSearchConfig::RANGE_MERCHANT_REFERENCE]][] = [
                (int)$additionalValues[MerchantProductOfferSearchConfig::RANGE_FROM_ID_PRODUCT_OFFER],
                (int)$additionalValues[MerchantProductOfferSearchConfig::RANGE_TO_ID_PRODUCT_OFFER],
            ];
        }

        return $productOfferIdRanges;
    }

    /**
     * @param array<int> $productAbstractIds
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function createProductAbstractEventEntityTransfers(array $productAbstractIds): array
    {
        $eventEntityTransfers = [];

        foreach ($productAbstractIds as $idProductAbstract) {
            $eventEntityTransfers[] = (new EventEntityTransfer())->setId($idProductAbstract);
        }

        return $eventEntityTransfers;
    }
}
