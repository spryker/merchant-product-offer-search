<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProductOfferSearch;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class MerchantProductOfferSearchConfig extends AbstractBundleConfig
{
    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_IS_ACTIVE
     */
    protected const string COL_MERCHANT_IS_ACTIVE = 'spy_merchant.is_active';

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_STATUS
     */
    protected const string COL_MERCHANT_STATUS = 'spy_merchant.status';

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_NAME
     */
    protected const string COL_MERCHANT_NAME = 'spy_merchant.name';

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap::COL_MERCHANT_REFERENCE
     */
    protected const string COL_MERCHANT_REFERENCE = 'spy_merchant.merchant_reference';

    /**
     * @uses \Orm\Zed\Merchant\Persistence\Map\SpyMerchantStoreTableMap::COL_FK_STORE
     */
    protected const string COL_MERCHANT_STORE_FK_STORE = 'spy_merchant_store.fk_store';

    protected const int MERCHANT_PRODUCT_OFFER_RANGE_SIZE = 50;

    protected const int MERCHANT_PRODUCT_OFFER_RANGE_READ_BATCH_SIZE = 1000;

    /**
     * @api
     *
     * @return string|null
     */
    public function getMerchantEventQueueName(): ?string
    {
        return null;
    }

    /**
     * @api
     *
     * @return string|null
     */
    public function getMerchantProductOfferEventQueueName(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the merchant columns that change the merchant data of the product abstract search.
     * - A merchant event that has modified columns republishes the product abstracts of the merchant only when one of these columns changed.
     * - A merchant event without modified columns always republishes the product abstracts of the merchant.
     *
     * @api
     *
     * @return list<string>
     */
    public function getProductAbstractSearchRelevantMerchantColumns(): array
    {
        return [
            static::COL_MERCHANT_IS_ACTIVE,
            static::COL_MERCHANT_STATUS,
            static::COL_MERCHANT_NAME,
            static::COL_MERCHANT_REFERENCE,
            static::COL_MERCHANT_STORE_FK_STORE,
        ];
    }

    /**
     * Specification:
     * - Returns the number of product offers in one range event that a merchant event triggers.
     * - The handler of one range event republishes the products of up to this number of product offers.
     * - A value below 1 counts as 1.
     *
     * @api
     */
    public function getMerchantProductOfferRangeSize(): int
    {
        return static::MERCHANT_PRODUCT_OFFER_RANGE_SIZE;
    }

    /**
     * Specification:
     * - Returns the number of product offer IDs that are read in one query to cut the product offers of a merchant into ranges.
     * - The range events of one query are triggered in one bulk.
     * - The number is rounded down to a multiple of {@link getMerchantProductOfferRangeSize()}, and is at least one range.
     *
     * @api
     */
    public function getMerchantProductOfferRangeReadBatchSize(): int
    {
        return static::MERCHANT_PRODUCT_OFFER_RANGE_READ_BATCH_SIZE;
    }
}
