<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Persistence;

use Generated\Shared\Transfer\ProductAbstractMerchantCollectionTransfer;
use Generated\Shared\Transfer\ProductAbstractMerchantCriteriaTransfer;

interface MerchantProductOfferSearchRepositoryInterface
{
    public function getProductAbstractMerchantCollection(
        ProductAbstractMerchantCriteriaTransfer $productAbstractMerchantCriteriaTransfer
    ): ProductAbstractMerchantCollectionTransfer;

    /**
     * @param array<int> $merchantIds
     *
     * @return array<int>
     */
    public function getProductAbstractIdsByMerchantIds(array $merchantIds): array;

    /**
     * @param array<int> $productOfferIds
     *
     * @return array<int>
     */
    public function getProductAbstractIdsByProductOfferIds(array $productOfferIds): array;

    /**
     * @param array<int> $productOfferIds
     *
     * @return array<int>
     */
    public function getProductConcreteIdsByProductOfferIds(array $productOfferIds): array;

    /**
     * @param array<int> $merchantIds
     *
     * @return list<string>
     */
    public function getMerchantReferencesByMerchantIds(array $merchantIds): array;

    /**
     * @return iterable<list<int>> Pages of up to `$batchSize` IDs of active product offers, ordered by ID.
     */
    public function iterateProductOfferIdsByMerchantReference(string $merchantReference, int $batchSize): iterable;

    /**
     * @param list<array{int, int}> $productOfferIdRanges The first and the last product offer ID of each range, both inclusive.
     *
     * @return list<\Generated\Shared\Transfer\ProductConcreteTransfer> The concrete products of the active product offers in the ranges.
     */
    public function getProductConcretesByMerchantReferenceAndProductOfferIdRanges(string $merchantReference, array $productOfferIdRanges): array;

    /**
     * Loads the active state of the merchants of all product offers of the given SKUs in one query, for later calls of `getActiveMerchantReferences()`.
     *
     * @param array<string> $productConcreteSkus
     */
    public function preloadMerchantStatesByProductConcreteSkus(array $productConcreteSkus): void;

    /**
     * Returns the given merchant references whose merchant is active. Reads the database only for references that are not preloaded.
     *
     * @param array<string> $merchantReferences
     *
     * @return array<string>
     */
    public function getActiveMerchantReferences(array $merchantReferences): array;
}
