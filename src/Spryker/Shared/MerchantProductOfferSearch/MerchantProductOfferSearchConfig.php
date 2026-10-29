<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\MerchantProductOfferSearch;

use Spryker\Shared\Kernel\AbstractBundleConfig;

class MerchantProductOfferSearchConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @var string
     */
    public const PLUGIN_PRODUCT_MERCHANT_DATA = 'PLUGIN_PRODUCT_MERCHANT_DATA';

    /**
     * Specification
     * - This events will be used for spy_product_offer entity creation.
     *
     * @api
     *
     * @uses \Spryker\Zed\MerchantProductOffer\Dependency\MerchantProductOfferEvents::ENTITY_SPY_PRODUCT_OFFER_CREATE
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_CREATE = 'Entity.spy_product_offer.create';

    /**
     * Specification
     * - This events will be used for spy_product_offer entity changes.
     *
     * @api
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_UPDATE = 'Entity.spy_product_offer.update';

    /**
     * Specification
     * - This events will be used for spy_product_offer entity deletion.
     *
     * @api
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_DELETE = 'Entity.spy_product_offer.delete';

    /**
     * Specification
     * - This events will be used for spy_product_offer_store entity creation.
     *
     * @api
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_STORE_CREATE = 'Entity.spy_product_offer_store.create';

    /**
     * Specification
     * - This events will be used for spy_product_offer_store entity changes.
     *
     * @api
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_STORE_UPDATE = 'Entity.spy_product_offer_store.update';

    /**
     * Specification
     * - This events will be used for spy_product_offer_store entity deletion.
     *
     * @api
     *
     * @var string
     */
    public const ENTITY_SPY_PRODUCT_OFFER_STORE_DELETE = 'Entity.spy_product_offer_store.delete';

    /**
     * Specification:
     * - Merchant product offer resource name, used for key generating.
     *
     * @api
     *
     * @var string
     */
    public const MERCHANT_PRODUCT_OFFER_RESOURCE_NAME = 'merchant_product_offer_search';

    /**
     * Specification:
     * - This event is used for merchant product offer publishing.
     *
     * @api
     *
     * @uses {@link \Spryker\Shared\ProductOfferStorage\ProductOfferStorageConfig::PRODUCT_OFFER_PUBLISH}
     *
     * @var string
     */
    public const PRODUCT_OFFER_PUBLISH = 'ProductOffer.product_offer.publish';

    /**
     * Specification
     * - This event triggers publishing of product abstracts solely for search purposes.
     *
     * @api
     *
     * @var string
     */
    public const PRODUCT_ABSTRACT_SEARCH_PUBLISH = 'Product.product_abstract_search.publish';

    /**
     * Specification:
     * - Queue name used for merchant product offer search publish events.
     *
     * @api
     *
     * @var string
     */
    public const string PUBLISH_MERCHANT_PRODUCT_OFFER_QUEUE = 'publish.merchant_product_offer';

    /**
     * Specification:
     * - This event is used to publish the concrete and the abstract product search pages of the products that have active product offers of one merchant in one product offer ID range.
     * - The additional values hold the merchant reference and the first and the last product offer ID of the range, both inclusive.
     * - The event has no ID, so the event queue consumer does not merge the range events of one merchant.
     *
     * @api
     */
    public const string MERCHANT_PRODUCT_OFFER_RANGE_PUBLISH = 'MerchantProductOfferSearch.merchant_product_offer_range.publish';

    /**
     * Specification:
     * - Additional value key of a range event: the merchant reference of the product offers.
     *
     * @api
     */
    public const string RANGE_MERCHANT_REFERENCE = 'merchant_reference';

    /**
     * Specification:
     * - Additional value key of a range event: the first product offer ID of the range, inclusive.
     *
     * @api
     */
    public const string RANGE_FROM_ID_PRODUCT_OFFER = 'from_id_product_offer';

    /**
     * Specification:
     * - Additional value key of a range event: the last product offer ID of the range, inclusive.
     *
     * @api
     */
    public const string RANGE_TO_ID_PRODUCT_OFFER = 'to_id_product_offer';
}
