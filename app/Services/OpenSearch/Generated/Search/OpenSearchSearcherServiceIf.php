<?php


namespace App\Services\OpenSearch\Generated\Search;


interface OpenSearchSearcherServiceIf extends \App\Services\OpenSearch\Generated\GeneralSearcher\GeneralSearcherServiceIf
{
    /**
     * @param \App\Services\OpenSearch\Generated\Search\SearchParams $searchParams
     * @return \App\Services\OpenSearch\Generated\GeneralSearcher\SearchResult
     * @throws \App\Services\OpenSearch\Generated\Common\OpenSearchException
     * @throws \App\Services\OpenSearch\Generated\Common\OpenSearchClientException
     */
    public function execute(\App\Services\OpenSearch\Generated\Search\SearchParams $searchParams);
}
