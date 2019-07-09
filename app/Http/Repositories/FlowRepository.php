<?php


namespace App\Http\Repositories;


class FlowRepository extends Repository
{
    public function pins($tags, $sort, $isUp, $specId, $time, $take)
    {
        return [
            'result' => [],
            'total' => 0,
            'no_more' => true
        ];
    }
}
