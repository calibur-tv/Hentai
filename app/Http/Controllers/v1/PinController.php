<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositorys\v1\PinRepository;
use App\Http\Repositorys\v1\TagRepository;
use App\Models\Pin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PinController extends Controller
{
    /**
     * 查看帖子（最好直接返回缓存，支持带 password 参数）
     */
    public function show_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|regex:/^[a-z0-9]+$/i',
            'key' => 'nullable|string',
            'ts' => 'nullable|integer'
        ]);

        if ($validator->fails())
        {
            return $this->resErrBad();
        }

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($request->get('slug'));

        if (is_null($pin))
        {
            return $this->resErrNotFound();
        }

        if ($pin['is_locked'])
        {
            return $this->resErrLocked();
        }

        if ($pin['is_secret'])
        {
            $key = $request->get('key');
            $ts = $request->get('ts');
            if (!$key || !$ts)
            {
                return $this->resErrRole();
            }

            if ($key !== md5('some-secret', $ts))
            {
                return $this->resErrRole('密码不正确');
            }

            if (abs(time() - $ts) < 3000)
            {
                return $this->resErrRole('密码已过期');
            }
        }

        return $this->resOK($pin);
    }

    /**
     * 返回无法缓存的数据（special的）
     */
    public function show_meta(Request $request)
    {
        $pin = Pin::find(189);

//        $pin->content()->create([
//            'text' => '123123'
//        ]);
        $pin->tags()->attach([
            1 => ['user_id' => 1],
            2 => ['user_id' => 2],
            3 => ['user_id' => 3]
        ]);

        return $this->resOK([
            env('DB_PASSWORD'),
            config('app.locale'),
            config('app.timezone'),
            config('purifier.encoding')
        ]);
    }

    /**
     * 创建帖子
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'array|required',
            'images.url' => 'required|string',
            'images.width' => 'required|integer',
            'images.height' => 'required|integer',
            'images.size' => 'required|integer',
            'images.type' => 'required|string',
            'title' => 'present|string|max:20',
            'content' => 'present|string',
            'origin_url' => 'required|url|max:30',
            'is_create' => 'required|boolean',
            'is_secret' => 'required|boolean',
            'is_bookmark' => 'required|boolean',
            'copyright_type' => 'required|integer'
        ]);
    }

    public function update(Request $request)
    {

    }

    /**
     * 给帖子设置标签
     */
    public function toggle_tag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin_slug' => 'required|string',
            'tag_slug' => 'required|string'
        ]);

        if ($validator->fails())
        {
            return $this->resErrBad();
        }

        $pinRepository = new PinRepository();
        $pin = $pinRepository->item($request->get('pin_slug'));
        if (is_null($pin))
        {
            return $this->resErrNotFound('不存在的内容');
        }

        $tagRepository = new TagRepository();
        $tag = $tagRepository->category_tags($request->get('tag_slug'));
        if (is_null($tag))
        {
            return $this->resErrNotFound('不存在的标签');
        }

        $user = $request->user();
        $record = PinTag
            ::where('pin_id', $pin->id)
            ->where('tag_id', $tag->id)
            ->where('user_id', $user->id)
            ->first();

        if (is_null($record))
        {
            PinTag::create([
                'pin_id' => $pin->id,
                'tag_id' => $tag->id,
                'user_id' => $user->id
            ]);

            Pin
                ::where('id', $pin->id)
                ->increment('tag_count');
        }
        else
        {
            $record->delete();

            Pin
                ::where('id', $pin->id)
                ->increment('tag_count', -1);
        }
        // TODO cache

        return $this->resNoContent();
    }

    /**
     * 删除帖子（作者或管理员）
     */
    public function destroy(Request $request)
    {

    }

    /**
     * 举报入口，修改 trial_type
     */
    public function report(Request $request)
    {

    }

    /**
     * 审核列表
     */
    public function trials(Request $request)
    {

    }

    /**
     * 审核通过
     */
    public function resolve(Request $request)
    {

    }

    /**
     * 审核不通过
     */
    public function reject(Request $request)
    {

    }
}
