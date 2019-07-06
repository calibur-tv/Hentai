<?php


namespace App\Services\Relation;


use Illuminate\Support\Facades\DB;

class RelationDetect
{
    public function batchDetect($result, $ids, $userId, $model, $relation, $key)
    {
        $model = ucfirst(strtolower($model));
        $model = $model === 'User' ? 'App\User' : "App\Models\\{$model}";

        $values = DB
            ::table('followables')
            ->where('user_id', $userId)
            ->where('relation', $relation)
            ->where('followable_type', $model)
            ->whereIn('followable_id', $ids)
            ->pluck('followable_id')
            ->toArray();

        $key = $key ?: $relation;
        foreach ($ids as $id)
        {
            isset($result[$id])
                ? $result[$id][$key] = in_array($id, $values)
                : $result[$id] = [$key => in_array($id, $values)];
        }

        return $result;
    }
}
