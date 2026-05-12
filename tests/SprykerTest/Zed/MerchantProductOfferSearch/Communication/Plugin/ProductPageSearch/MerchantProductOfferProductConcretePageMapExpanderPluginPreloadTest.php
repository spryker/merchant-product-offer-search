<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\MerchantProductOfferSearch\Communication\Plugin\ProductPageSearch;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use ReflectionClass;
use Spryker\Zed\MerchantProductOffer\Business\MerchantProductOfferReader\MerchantProductOfferReader;
use Spryker\Zed\MerchantProductOfferSearch\Communication\Plugin\ProductPageSearch\MerchantProductOfferProductConcretePageMapExpanderPlugin;
use SprykerTest\Zed\MerchantProductOfferSearch\MerchantProductOfferSearchCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group MerchantProductOfferSearch
 * @group Communication
 * @group Plugin
 * @group ProductPageSearch
 * @group MerchantProductOfferProductConcretePageMapExpanderPluginPreloadTest
 * Add your own group annotations below this line
 */
class MerchantProductOfferProductConcretePageMapExpanderPluginPreloadTest extends Unit
{
    protected const string DEFAULT_STORE = 'DE';

    /**
     * @uses \Spryker\Shared\ProductOffer\ProductOfferConfig::STATUS_APPROVED
     */
    protected const string PRODUCT_OFFER_APPROVAL_STATUS_APPROVED = 'approved';

    /**
     * @uses \Spryker\Shared\ProductOffer\ProductOfferConfig::STATUS_DECLINED
     */
    protected const string PRODUCT_OFFER_APPROVAL_STATUS_DECLINED = 'declined';

    protected MerchantProductOfferSearchCommunicationTester $tester;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearReaderCache();
    }

    /**
     * Verifies that preload populates the reader cache per SKU and store,
     * and that the cache entry reflects the exact offer count returned by the repository.
     * Note: preload does NOT filter by is_active or approval_status — that filtering
     * happens later in expandProductConcretePageMap.
     *
     * @dataProvider preloadDataProvider
     */
    public function testPreloadPopulatesOfferCachePerSkuAndStore(
        int $offerCount,
        bool $isActive,
        string $approvalStatus,
        int $expectedOfferCount,
    ): void {
        // Arrange
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::DEFAULT_STORE]);
        $productConcreteTransfer = $this->tester->haveProduct([ProductConcreteTransfer::IS_ACTIVE => true]);
        $productConcreteTransfer->addStores($storeTransfer);
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);

        for ($i = 0; $i < $offerCount; $i++) {
            $this->tester->haveProductOffer([
                ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSku(),
                ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
                ProductOfferTransfer::IS_ACTIVE => $isActive,
                ProductOfferTransfer::APPROVAL_STATUS => $approvalStatus,
                ProductOfferTransfer::STORES => new ArrayObject([$storeTransfer]),
            ]);
        }

        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([$productConcreteTransfer]);

        // Assert — cache is indexed per SKU then per store ID
        $cache = $this->getReaderCache();
        $sku = $productConcreteTransfer->getSkuOrFail();
        $storeId = $storeTransfer->getIdStoreOrFail();

        $this->assertArrayHasKey($sku, $cache, sprintf('Expected SKU "%s" to be present in reader cache after preload.', $sku));
        $this->assertArrayHasKey($storeId, $cache[$sku], sprintf('Expected store ID "%d" to be present in reader cache for SKU "%s".', $storeId, $sku));
        $this->assertCount($expectedOfferCount, $cache[$sku][$storeId]->getProductOffers());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function preloadDataProvider(): array
    {
        return [
            'active approved offer is loaded into cache' => [
                'offerCount' => 1,
                'isActive' => true,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
                'expectedOfferCount' => 1,
            ],
            'inactive offer is loaded into cache without filtering' => [
                'offerCount' => 1,
                'isActive' => false,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
                'expectedOfferCount' => 1,
            ],
            'unapproved offer is loaded into cache without filtering' => [
                'offerCount' => 1,
                'isActive' => true,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_DECLINED,
                'expectedOfferCount' => 1,
            ],
            'inactive and unapproved offer is loaded into cache without filtering' => [
                'offerCount' => 1,
                'isActive' => false,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_DECLINED,
                'expectedOfferCount' => 1,
            ],
            'product without offers results in empty cache entry' => [
                'offerCount' => 0,
                'isActive' => true,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
                'expectedOfferCount' => 0,
            ],
            'multiple offers for same sku are all loaded into cache' => [
                'offerCount' => 3,
                'isActive' => true,
                'approvalStatus' => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
                'expectedOfferCount' => 3,
            ],
        ];
    }

    /**
     * Verifies that preloading multiple products at once caches each SKU independently.
     */
    public function testPreloadCachesAllSkusFromMultipleProducts(): void
    {
        // Arrange
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::DEFAULT_STORE]);
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);

        $firstProduct = $this->tester->haveProduct([ProductConcreteTransfer::IS_ACTIVE => true]);
        $firstProduct->addStores($storeTransfer);
        $secondProduct = $this->tester->haveProduct([ProductConcreteTransfer::IS_ACTIVE => true]);
        $secondProduct->addStores($storeTransfer);

        foreach ([$firstProduct, $secondProduct] as $productConcreteTransfer) {
            $this->tester->haveProductOffer([
                ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSku(),
                ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
                ProductOfferTransfer::IS_ACTIVE => true,
                ProductOfferTransfer::APPROVAL_STATUS => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
                ProductOfferTransfer::STORES => new ArrayObject([$storeTransfer]),
            ]);
        }

        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([$firstProduct, $secondProduct]);

        // Assert
        $cache = $this->getReaderCache();
        $storeId = $storeTransfer->getIdStoreOrFail();

        foreach ([$firstProduct, $secondProduct] as $productConcreteTransfer) {
            $sku = $productConcreteTransfer->getSkuOrFail();
            $this->assertArrayHasKey($sku, $cache);
            $this->assertCount(1, $cache[$sku][$storeId]->getProductOffers());
        }
    }

    /**
     * Verifies per-store offer isolation:
     * - Offer 1 is available in both stores → appears in cache for both store IDs.
     * - Offer 2 is available in Store A only → appears only in Store A's cache entry.
     */
    public function testPreloadCachesOffersCorrectlyAcrossMultipleStores(): void
    {
        // Arrange
        $storeA = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $storeB = $this->tester->haveStore([StoreTransfer::NAME => 'AT']);

        $productConcreteTransfer = $this->tester->haveProduct([ProductConcreteTransfer::IS_ACTIVE => true]);
        $productConcreteTransfer->addStores($storeA);
        $productConcreteTransfer->addStores($storeB);
        $merchantTransfer = $this->tester->haveMerchant([MerchantTransfer::IS_ACTIVE => true]);

        // Offer available in both stores
        $offerInBothStores = $this->tester->haveProductOffer([
            ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSku(),
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
            ProductOfferTransfer::IS_ACTIVE => true,
            ProductOfferTransfer::APPROVAL_STATUS => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
            ProductOfferTransfer::STORES => new ArrayObject([$storeA, $storeB]),
        ]);

        // Offer available in Store A only
        $offerInStoreAOnly = $this->tester->haveProductOffer([
            ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSku(),
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference(),
            ProductOfferTransfer::IS_ACTIVE => true,
            ProductOfferTransfer::APPROVAL_STATUS => static::PRODUCT_OFFER_APPROVAL_STATUS_APPROVED,
            ProductOfferTransfer::STORES => new ArrayObject([$storeA]),
        ]);

        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([$productConcreteTransfer]);

        // Assert
        $cache = $this->getReaderCache();
        $sku = $productConcreteTransfer->getSkuOrFail();
        $storeAId = $storeA->getIdStoreOrFail();
        $storeBId = $storeB->getIdStoreOrFail();

        $this->assertArrayHasKey($sku, $cache);

        // Store A: both offers should be cached
        $this->assertArrayHasKey($storeAId, $cache[$sku]);
        $cachedOfferReferencesForStoreA = $this->extractProductOfferReferences($cache[$sku][$storeAId]->getProductOffers()->getArrayCopy());
        $this->assertContains($offerInBothStores->getProductOfferReference(), $cachedOfferReferencesForStoreA);
        $this->assertContains($offerInStoreAOnly->getProductOfferReference(), $cachedOfferReferencesForStoreA);

        // Store B: only the offer available in both stores should be cached
        $this->assertArrayHasKey($storeBId, $cache[$sku]);
        $cachedOfferReferencesForStoreB = $this->extractProductOfferReferences($cache[$sku][$storeBId]->getProductOffers()->getArrayCopy());
        $this->assertContains($offerInBothStores->getProductOfferReference(), $cachedOfferReferencesForStoreB);
        $this->assertNotContains($offerInStoreAOnly->getProductOfferReference(), $cachedOfferReferencesForStoreB);
    }

    /**
     * Negative: empty input → preload short-circuits before any DB query, cache stays empty.
     */
    public function testPreloadWithEmptyProductListDoesNotPopulateCache(): void
    {
        // Arrange
        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([]);

        // Assert
        $this->assertEmpty($this->getReaderCache(), 'Expected reader cache to remain empty when no product concretes are given.');
    }

    /**
     * Negative: product concrete without a SKU → filtered out before DB query, cache stays empty.
     */
    public function testPreloadWithProductHavingNullSkuDoesNotPopulateCache(): void
    {
        // Arrange
        $productConcreteTransfer = new ProductConcreteTransfer();
        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([$productConcreteTransfer]);

        // Assert
        $this->assertEmpty($this->getReaderCache(), 'Expected reader cache to remain empty when all product SKUs are null.');
    }

    /**
     * Negative: product concrete with no stores attached → storeIds is empty,
     * preload short-circuits before DB query, cache stays empty.
     */
    public function testPreloadWithProductHavingNoStoresDoesNotPopulateCache(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct([ProductConcreteTransfer::IS_ACTIVE => true]);
        $plugin = new MerchantProductOfferProductConcretePageMapExpanderPlugin();

        // Act
        $plugin->preload([$productConcreteTransfer]);

        // Assert
        $this->assertEmpty($this->getReaderCache(), 'Expected reader cache to remain empty when product has no stores attached.');
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferTransfer> $productOfferTransfers
     *
     * @return array<string>
     */
    protected function extractProductOfferReferences(array $productOfferTransfers): array
    {
        return array_values(array_filter(array_map(
            fn ($offer) => $offer->getProductOfferReference(),
            $productOfferTransfers,
        )));
    }

    /**
     * @return array<string, array<int, \Generated\Shared\Transfer\ProductOfferCollectionTransfer>>
     */
    protected function getReaderCache(): array
    {
        $reflection = new ReflectionClass(MerchantProductOfferReader::class);

        return $reflection->getProperty('merchantProductOfferCollectionCache')->getValue();
    }

    protected function clearReaderCache(): void
    {
        $reflection = new ReflectionClass(MerchantProductOfferReader::class);
        $reflection->getProperty('merchantProductOfferCollectionCache')->setValue(null, []);
    }
}
