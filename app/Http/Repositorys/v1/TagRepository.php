<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositorys\v1;


use App\Http\Repositories\Repository;
use App\Http\Transformers\TagResource;
use App\Models\Tag;

class TagRepository extends Repository
{
    public function item($slug)
    {
        $result = $this->Cache($this->tag_cache_key($slug), function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return new TagResource($tag);
        });

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function relation_item($slug)
    {
        $result = $this->Cache($this->category_tags_cache_key($slug), function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return [
                'tag' => new TagResource($tag),
                'parent' => $tag->parent_slug ? new TagResource($tag->parent()->first()) : null,
                'children' => TagResource::collection($tag->children()->get())
            ];
        }, 'd', true);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function tag_cache_key($slug)
    {
        return "tag-{$slug}";
    }

    public function category_tags_cache_key($slug)
    {
        return "tag-category-{$slug}";
    }
}
