<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Question;

use App\Http\Transformers\Bangumi\BangumiItemResource;
use App\Http\Transformers\User\UserItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'answers' => json_decode($this->answers),
            'bangumi' => new BangumiItemResource($this->bangumi),
            'user' => new UserItemResource($this->author),
            'status' => $this->status
        ];
    }
}
