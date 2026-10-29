<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch\Persistence;

use Generated\Shared\Transfer\ProductAbstractMerchantCollectionTransfer;
use Generated\Shared\Transfer\ProductAbstractMerchantConditionsTransfer;
use Generated\Shared\Transfer\ProductAbstractMerchantCriteriaTransfer;
use Generated\Shared\Transfer\ProductAbstractMerchantTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantStoreTableMap;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Orm\Zed\Store\Persistence\Map\SpyStoreTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Model\Formatter\OptimizedSimpleArrayFormatter;

/**
 * @method \Spryker\Zed\MerchantProductOfferSearch\Persistence\MerchantProductOfferSearchPersistenceFactory getFactory()
 */
class MerchantProductOfferSearchRepository extends AbstractRepository implements MerchantProductOfferSearchRepositoryInterface
{
    /**
     * @var string
     */
    protected const KEY_ABSTRACT_PRODUCT_ID = 'id';

    protected const string KEY_PRODUCT_ID = 'product_id';

    protected const string KEY_MERCHANT_IS_ACTIVE = 'merchant_is_active';

    /**
     * Static, because the business factory creates a new repository for each page map expansion; the preload of a batch fills the cache that the expansion of each product reads.
     *
     * @var array<string, bool>
     */
    protected static array $isActiveByMerchantReference = [];

    /**
     * @var string
     */
    protected const KEY_MERCHANT_NAME = 'name';

    /**
     * @var string
     */
    protected const KEY_MERCHANT_REFERENCE = 'reference';

    /**
     * @var string
     */
    protected const KEY_MERCHANT_NAMES = 'names';

    /**
     * @var string
     */
    protected const KEY_MERCHANT_REFERENCES = 'references';

    /**
     * @var string
     */
    protected const KEY_STORE_NAME = 'storeName';

    /**
     * @module Store
     * @module Merchant
     * @module ProductOffer
     */
    public function getProductAbstractMerchantCollection(
        ProductAbstractMerchantCriteriaTransfer $productAbstractMerchantCriteriaTransfer
    ): ProductAbstractMerchantCollectionTransfer {
        $productOfferPropelQuery = $this->getFactory()->getProductOfferPropelQuery();
        $productAbstractMerchantConditionsTransfer = $productAbstractMerchantCriteriaTransfer->getProductAbstractMerchantConditionsOrFail();
        $productOfferPropelQuery
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productOfferPropelQuery->getNewCriterion(SpyMerchantTableMap::COL_IS_ACTIVE, true, Criteria::EQUAL))
            ->addJoin(SpyMerchantTableMap::COL_ID_MERCHANT, SpyMerchantStoreTableMap::COL_FK_MERCHANT, Criteria::INNER_JOIN)
            ->addJoin(SpyMerchantStoreTableMap::COL_FK_STORE, SpyStoreTableMap::COL_ID_STORE, Criteria::INNER_JOIN)
            ->addJoin(SpyProductOfferTableMap::COL_CONCRETE_SKU, SpyProductTableMap::COL_SKU, Criteria::INNER_JOIN)
            ->addJoin(SpyProductTableMap::COL_FK_PRODUCT_ABSTRACT, SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT, Criteria::INNER_JOIN)
            ->addAnd($productOfferPropelQuery->getNewCriterion(
                SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT,
                $productAbstractMerchantConditionsTransfer->getProductAbstractIds(),
                Criteria::IN,
            ));

        $productOfferPropelQuery = $this->applyProductAbstractMerchantFilters(
            $productOfferPropelQuery,
            $productAbstractMerchantConditionsTransfer,
        );

        $merchantData = $productOfferPropelQuery
            ->select([static::KEY_ABSTRACT_PRODUCT_ID, static::KEY_MERCHANT_NAME, static::KEY_MERCHANT_REFERENCE])
            ->withColumn(SpyMerchantTableMap::COL_NAME, static::KEY_MERCHANT_NAME)
            ->withColumn(SpyMerchantTableMap::COL_MERCHANT_REFERENCE, static::KEY_MERCHANT_REFERENCE)
            ->withColumn(SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT, static::KEY_ABSTRACT_PRODUCT_ID)
            ->withColumn(SpyStoreTableMap::COL_NAME, static::KEY_STORE_NAME)
            ->orderBy(static::KEY_ABSTRACT_PRODUCT_ID)
            ->find()
            ->getData();

        $groupedMerchantDataByProductAbstractId = $this->groupMerchantDataByProductAbstractId($merchantData);

        $productAbstractMerchantMapper = $this->getFactory()->createProductAbstractMerchantMapper();
        $productAbstractMerchantCollectionTransfer = new ProductAbstractMerchantCollectionTransfer();

        foreach ($groupedMerchantDataByProductAbstractId as $idProductAbstract => $productAbstractMerchantData) {
            $productAbstractMerchantCollectionTransfer->addProductAbstractMerchant(
                $productAbstractMerchantMapper->mapProductAbstractMerchantDataToProductAbstractMerchantTransfer(
                    $productAbstractMerchantData,
                    new ProductAbstractMerchantTransfer(),
                ),
            );
        }

        return $productAbstractMerchantCollectionTransfer;
    }

    /**
     * @param array<int> $merchantIds
     *
     * @return array<int>
     */
    public function getProductAbstractIdsByMerchantIds(array $merchantIds): array
    {
        $productAbstractPropelQuery = $this->getFactory()->getProductAbstractPropelQuery();
        $productAbstractPropelQuery
            ->innerJoinSpyProduct()
            ->addJoin(SpyProductTableMap::COL_SKU, SpyProductOfferTableMap::COL_CONCRETE_SKU, Criteria::INNER_JOIN)
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productAbstractPropelQuery->getNewCriterion(SpyMerchantTableMap::COL_ID_MERCHANT, $merchantIds, Criteria::IN));

        return $productAbstractPropelQuery
            ->where(SpyProductOfferTableMap::COL_IS_ACTIVE, true)
            ->select([SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT])
            ->find()
            ->getData();
    }

    /**
     * @param array<int> $productOfferIds
     *
     * @return array<int>
     */
    public function getProductAbstractIdsByProductOfferIds(array $productOfferIds): array
    {
        $productAbstractPropelQuery = $this->getFactory()->getProductAbstractPropelQuery();
        $productAbstractPropelQuery
            ->innerJoinSpyProduct()
            ->addJoin(SpyProductTableMap::COL_SKU, SpyProductOfferTableMap::COL_CONCRETE_SKU, Criteria::INNER_JOIN)
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productAbstractPropelQuery->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $productOfferIds, Criteria::IN));

        return $productAbstractPropelQuery
            ->where(SpyMerchantTableMap::COL_IS_ACTIVE, true)
            ->select([SpyProductAbstractTableMap::COL_ID_PRODUCT_ABSTRACT])
            ->find()
            ->getData();
    }

    /**
     * @param array<int> $productOfferIds
     *
     * @return array<int>
     */
    public function getProductConcreteIdsByProductOfferIds(array $productOfferIds): array
    {
        $productAbstractPropelQuery = $this->getFactory()->getProductAbstractPropelQuery();
        $productAbstractPropelQuery
            ->innerJoinSpyProduct()
            ->addJoin(SpyProductTableMap::COL_SKU, SpyProductOfferTableMap::COL_CONCRETE_SKU, Criteria::INNER_JOIN)
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productAbstractPropelQuery->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $productOfferIds, Criteria::IN));

        return $productAbstractPropelQuery
            ->where(SpyMerchantTableMap::COL_IS_ACTIVE, true)
            ->select([SpyProductTableMap::COL_ID_PRODUCT])
            ->find()
            ->getData();
    }

    /**
     * @return iterable<list<int>>
     */
    public function iterateProductOfferIdsByMerchantReference(string $merchantReference, int $batchSize): iterable
    {
        $lastIdProductOffer = 0;

        do {
            /** @var list<int> $productOfferIds */
            $productOfferIds = $this->getFactory()
                ->getProductOfferPropelQuery()
                ->filterByMerchantReference($merchantReference)
                ->filterByIdProductOffer($lastIdProductOffer, Criteria::GREATER_THAN)
                ->filterByIsActive(true)
                ->select([SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER])
                ->orderByIdProductOffer()
                ->setLimit($batchSize)
                ->find()
                ->getData();

            if (!$productOfferIds) {
                break;
            }

            $productOfferIds = array_map('intval', $productOfferIds);
            $lastIdProductOffer = end($productOfferIds);

            yield $productOfferIds;
        } while (count($productOfferIds) === $batchSize);
    }

    /**
     * @module Product
     *
     * @param list<array{int, int}> $productOfferIdRanges
     *
     * @return list<\Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    public function getProductConcretesByMerchantReferenceAndProductOfferIdRanges(string $merchantReference, array $productOfferIdRanges): array
    {
        if (!$productOfferIdRanges) {
            return [];
        }

        $productOfferQuery = $this->getFactory()
            ->getProductOfferPropelQuery()
            ->addJoin(SpyProductOfferTableMap::COL_CONCRETE_SKU, SpyProductTableMap::COL_SKU, Criteria::INNER_JOIN)
            ->filterByMerchantReference($merchantReference);

        /** @var array<array<string, mixed>> $productRows */
        $productRows = $productOfferQuery
            ->addAnd($this->createProductOfferIdRangesCriterion($productOfferQuery, $productOfferIdRanges))
            ->filterByIsActive(true)
            ->withColumn(SpyProductTableMap::COL_ID_PRODUCT, static::KEY_PRODUCT_ID)
            ->withColumn(SpyProductTableMap::COL_FK_PRODUCT_ABSTRACT, static::KEY_ABSTRACT_PRODUCT_ID)
            ->select([static::KEY_PRODUCT_ID, static::KEY_ABSTRACT_PRODUCT_ID])
            ->setFormatter(new OptimizedSimpleArrayFormatter())
            ->find()
            ->getData();

        $productConcreteTransfers = [];
        foreach ($productRows as $productRow) {
            $productConcreteTransfers[] = (new ProductConcreteTransfer())
                ->setIdProductConcrete((int)$productRow[static::KEY_PRODUCT_ID])
                ->setFkProductAbstract((int)$productRow[static::KEY_ABSTRACT_PRODUCT_ID]);
        }

        return $productConcreteTransfers;
    }

    /**
     * @param array<string> $productConcreteSkus
     */
    public function preloadMerchantStatesByProductConcreteSkus(array $productConcreteSkus): void
    {
        static::$isActiveByMerchantReference = [];

        if (!$productConcreteSkus) {
            return;
        }

        $productOfferPropelQuery = $this->getFactory()->getProductOfferPropelQuery();
        $productOfferPropelQuery
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productOfferPropelQuery->getNewCriterion(SpyProductOfferTableMap::COL_CONCRETE_SKU, array_values(array_unique($productConcreteSkus)), Criteria::IN));

        $this->addMerchantStatesToCache($productOfferPropelQuery);
    }

    /**
     * @param array<string> $merchantReferences
     *
     * @return array<string>
     */
    public function getActiveMerchantReferences(array $merchantReferences): array
    {
        $merchantReferences = array_values(array_unique($merchantReferences));
        $unknownMerchantReferences = array_values(array_filter(
            $merchantReferences,
            fn (string $merchantReference): bool => !array_key_exists($merchantReference, static::$isActiveByMerchantReference),
        ));

        if ($unknownMerchantReferences) {
            $this->addUnknownMerchantStatesToCache($unknownMerchantReferences);
        }

        return array_values(array_filter(
            $merchantReferences,
            fn (string $merchantReference): bool => static::$isActiveByMerchantReference[$merchantReference],
        ));
    }

    /**
     * @param array<string> $merchantReferences
     */
    protected function addUnknownMerchantStatesToCache(array $merchantReferences): void
    {
        foreach ($merchantReferences as $merchantReference) {
            static::$isActiveByMerchantReference[$merchantReference] = false;
        }

        $productOfferPropelQuery = $this->getFactory()->getProductOfferPropelQuery();
        $productOfferPropelQuery
            ->addJoin(SpyProductOfferTableMap::COL_MERCHANT_REFERENCE, SpyMerchantTableMap::COL_MERCHANT_REFERENCE, Criteria::INNER_JOIN)
            ->addAnd($productOfferPropelQuery->getNewCriterion(SpyMerchantTableMap::COL_MERCHANT_REFERENCE, $merchantReferences, Criteria::IN));

        $this->addMerchantStatesToCache($productOfferPropelQuery);
    }

    protected function addMerchantStatesToCache(SpyProductOfferQuery $productOfferPropelQuery): void
    {
        /** @var array<array<string, mixed>> $merchantStateRows */
        $merchantStateRows = $productOfferPropelQuery
            ->withColumn(SpyMerchantTableMap::COL_MERCHANT_REFERENCE, static::KEY_MERCHANT_REFERENCE)
            ->withColumn(SpyMerchantTableMap::COL_IS_ACTIVE, static::KEY_MERCHANT_IS_ACTIVE)
            ->select([static::KEY_MERCHANT_REFERENCE, static::KEY_MERCHANT_IS_ACTIVE])
            ->distinct()
            ->setFormatter(new OptimizedSimpleArrayFormatter())
            ->find()
            ->getData();

        foreach ($merchantStateRows as $merchantStateRow) {
            static::$isActiveByMerchantReference[$merchantStateRow[static::KEY_MERCHANT_REFERENCE]] = (bool)$merchantStateRow[static::KEY_MERCHANT_IS_ACTIVE];
        }
    }

    protected function groupMerchantDataByProductAbstractId(array $merchantData): array
    {
        $groupedProductAbstractMerchantData = [];

        foreach ($merchantData as $productAbstractMerchant) {
            $idProductAbstract = $productAbstractMerchant[static::KEY_ABSTRACT_PRODUCT_ID];
            $merchantName = $productAbstractMerchant[static::KEY_MERCHANT_NAME];
            $merchantReference = $productAbstractMerchant[static::KEY_MERCHANT_REFERENCE];
            $storeName = $productAbstractMerchant[static::KEY_STORE_NAME];

            $groupedProductAbstractMerchantData[$idProductAbstract][static::KEY_MERCHANT_NAMES][$storeName][] = $merchantName;
            $groupedProductAbstractMerchantData[$idProductAbstract][static::KEY_MERCHANT_REFERENCES][$storeName][] = $merchantReference;
            $groupedProductAbstractMerchantData[$idProductAbstract][static::KEY_ABSTRACT_PRODUCT_ID] = $idProductAbstract;
        }

        return $groupedProductAbstractMerchantData;
    }

    protected function applyProductAbstractMerchantFilters(
        SpyProductOfferQuery $productOfferQuery,
        ProductAbstractMerchantConditionsTransfer $productAbstractMerchantConditionsTransfer
    ): SpyProductOfferQuery {
        if ($productAbstractMerchantConditionsTransfer->getIsProductOfferActive() !== null) {
            $productOfferQuery->filterByIsActive($productAbstractMerchantConditionsTransfer->getIsProductOfferActiveOrFail());
        }

        if ($productAbstractMerchantConditionsTransfer->getProductOfferApprovalStatuses()) {
            $productOfferQuery->filterByApprovalStatus_In($productAbstractMerchantConditionsTransfer->getProductOfferApprovalStatuses());
        }

        return $productOfferQuery;
    }

    /**
     * @param list<array{int, int}> $productOfferIdRanges
     */
    protected function createProductOfferIdRangesCriterion(SpyProductOfferQuery $productOfferQuery, array $productOfferIdRanges): AbstractCriterion
    {
        [$fromIdProductOffer, $toIdProductOffer] = $productOfferIdRanges[0];
        $productOfferIdRangesCriterion = $this->createProductOfferIdRangeCriterion($productOfferQuery, $fromIdProductOffer, $toIdProductOffer);

        foreach (array_slice($productOfferIdRanges, 1) as [$fromIdProductOffer, $toIdProductOffer]) {
            $productOfferIdRangesCriterion->addOr($this->createProductOfferIdRangeCriterion($productOfferQuery, $fromIdProductOffer, $toIdProductOffer));
        }

        return $productOfferIdRangesCriterion;
    }

    protected function createProductOfferIdRangeCriterion(
        SpyProductOfferQuery $productOfferQuery,
        int $fromIdProductOffer,
        int $toIdProductOffer
    ): AbstractCriterion {
        return $productOfferQuery
            ->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $fromIdProductOffer, Criteria::GREATER_EQUAL)
            ->addAnd($productOfferQuery->getNewCriterion(SpyProductOfferTableMap::COL_ID_PRODUCT_OFFER, $toIdProductOffer, Criteria::LESS_EQUAL));
    }

    /**
     * @param array<int> $merchantIds
     *
     * @return list<string>
     */
    public function getMerchantReferencesByMerchantIds(array $merchantIds): array
    {
        if (!$merchantIds) {
            return [];
        }

        /** @var list<string> $merchantReferences */
        $merchantReferences = $this->getFactory()
            ->getMerchantPropelQuery()
            ->filterByIdMerchant_In($merchantIds)
            ->select([SpyMerchantTableMap::COL_MERCHANT_REFERENCE])
            ->find()
            ->getData();

        return $merchantReferences;
    }
}
