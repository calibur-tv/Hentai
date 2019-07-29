<?php

namespace App\Services\OpenSearch;
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/2/11
 * Time: 上午11:02
 */
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Http\Repositories\UserRepository;
use App\Services\OpenSearch\Client\OpenSearchClient;
use App\Services\OpenSearch\Client\SearchClient;
use App\Services\OpenSearch\Util\SearchParamsBuilder;

class Search
{
    protected $accessKeyId;
    protected $secret;
    protected $endPoint;
    protected $appName;
    protected $format = 'json';
    protected $options = ['debug' => false];
    protected $client;
    protected $search;
    protected $params;

    public function __construct()
    {
        $this->accessKeyId = config('app.search.access');
        $this->secret = config('app.search.secret');
        $this->endPoint = config('app.search.endpoint');
        $this->appName = config('app.search.name');
        $this->client = new OpenSearchClient($this->accessKeyId, $this->secret, $this->endPoint, $this->options);
        $this->search = new SearchClient($this->client);
        $this->params = new SearchParamsBuilder();
    }

    public function retrieve($key, $type = 'all', $page = 0, $count = 15)
    {
        $typeId = $this->convertModal($type);
        $this->params->setStart($page * $count);
        $this->params->setHits($count);
        $this->params->setAppName($this->appName);
        $this->params->setFormat($this->format);
        $this->params->setQuery(
            $typeId
                ? "text:'${key}' AND type:'" . $typeId . "'&&sort=-(score)"
                : "text:'${key}'&&sort=-(score)"
        );

        $res = json_decode($this->search->execute($this->params->build())->result, true);

        if ($res['status'] !== 'OK')
        {
            return [
                'total' => 0,
                'result' => [],
                'no_more' => true
            ];
        }

        $ret = $res['result'];
        $list = $ret['items'];

        $result = [];
        if ($typeId)
        {
            $slug = array_map(function ($item)
            {
                return $item['slug'];
            }, $list);
            $repository = $this->getRepositoryByType($typeId);
            $result = $repository->list($slug);
        }
        else
        {
            foreach ($list as $item)
            {
                $typeId = $item['type'];
                $slug = $item['slug'];
                $repository = $this->getRepositoryByType($typeId);
                $item = $repository->item($slug);
                if (!$item)
                {
                    \App\Models\Search
                        ::where('slug', $slug)
                        ->where('type', $typeId)
                        ->delete();

                    continue;
                }
                $result[] = [
                    'type' => $this->convertModal($typeId),
                    'data' => $item
                ];
            }
        }

        return [
            'result' => $result,
            'total' => $ret['total'],
            'no_more' => $ret['num'] < $count
        ];
    }

    public function convertModal($modal)
    {
        $arr = [
            'all' => 0,
            'pin' => 1,
            'tag' => 2,
            'user' => 3
        ];

        try
        {
            if (gettype($modal) === 'string')
            {
                return $arr[$modal] ?: 0;
            }

            return array_flip($arr)[$modal] ?: 'all';
        }
        catch (\Exception $e)
        {
            return gettype($modal) === 'string' ? 0 : 'all';
        }
    }

    public function getRepositoryByType($type)
    {
        if ($type == 1)
        {
            return new PinRepository();
        }
        else if ($type == 2)
        {
            return new TagRepository();
        }
        else if ($type == 3)
        {
            return new UserRepository();
        }

        return null;
    }
}
