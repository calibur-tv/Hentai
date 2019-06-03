<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositorys\v1;


use App\Http\Repositories\Repository;
use App\Http\Transformers\Tag\TagBodyResource;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\Tag;

class TagRepository extends Repository
{
    public function item($slug, $refresh = false)
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

            return new TagItemResource($tag);
        }, $refresh);

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
                'tag' => new TagBodyResource($tag),
                'parent' => $tag->parent_slug ? new TagItemResource($tag->parent()->first()) : null,
                'children' => TagItemResource::collection($tag->children()->get())
            ];
        },true);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function tag_cache_key($slug)
    {
        return "tag:{$slug}";
    }

    public function category_tags_cache_key($slug)
    {
        return "tag-category:{$slug}";
    }
}
