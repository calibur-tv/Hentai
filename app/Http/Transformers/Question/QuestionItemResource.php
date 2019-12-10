<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\Question;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'answers' => json_decode($this->answers),
            'bangumi_slug' => $this->bangumi_slug,
            'user_slug' => $this->user_slug,
            'status' => $this->status
        ];
    }
}
