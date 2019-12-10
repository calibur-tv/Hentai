<?php


namespace App\Http\Repositories;


use App\Http\Transformers\Question\QuestionItemResource;
use App\Models\BangumiQuestion;

class QuestionRepository extends Repository
{
    public function item($id, $refresh = false)
    {
        if (!$id)
        {
            return null;
        }

        $result = $this->RedisItem("question:{$id}", function () use ($id)
        {
            $question = BangumiQuestion
                ::where('id', $id)
                ->first();

            if (is_null($question))
            {
                return 'nil';
            }

            return new QuestionItemResource($question);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }
}
