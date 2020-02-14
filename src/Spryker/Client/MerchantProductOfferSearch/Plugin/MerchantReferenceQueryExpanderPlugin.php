<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Client\MerchantProductOfferSearch\Plugin;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Generated\Shared\Search\PageIndexMap;
use InvalidArgumentException;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

class MerchantReferenceQueryExpanderPlugin extends AbstractPlugin implements QueryExpanderPluginInterface
{
    protected const MERCHANT_REFERENCE = 'merchant_reference';

    /**
     * {@inheritDoc}
     * - Adds filter by merchant reference to query.
     *
     * @api
     *
     * @param \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface $searchQuery
     * @param array $requestParameters
     *
     * @return \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface
     */
    public function expandQuery(QueryInterface $searchQuery, array $requestParameters = []): QueryInterface
    {
        $this->addMerchantReferenceFilterToQuery($searchQuery->getSearchQuery(), $requestParameters);

        return $searchQuery;
    }

    /**
     * @param \Elastica\Query $query
     * @param array $requestParameters
     *
     * @return void
     */
    protected function addMerchantReferenceFilterToQuery(Query $query, array $requestParameters = []): void
    {
        $boolQuery = $this->getBoolQuery($query);

        $merchantReference = $requestParameters[static::MERCHANT_REFERENCE];

        if ($merchantReference) {
            $matchQuery = (new Match())->setField(PageIndexMap::MERCHANT_REFERENCES, $merchantReference);

            $boolQuery->addMust($matchQuery);
        }
    }

    /**
     * @param \Elastica\Query $query
     *
     * @throws \InvalidArgumentException
     *
     * @return \Elastica\Query\BoolQuery
     */
    protected function getBoolQuery(Query $query): BoolQuery
    {
        $boolQuery = $query->getQuery();
        if (!$boolQuery instanceof BoolQuery) {
            throw new InvalidArgumentException(sprintf(
                'Localized query expander available only with %s, got: %s',
                BoolQuery::class,
                get_class($boolQuery)
            ));
        }

        return $boolQuery;
    }
}
