<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-27
 * Time: 20:08
 */

namespace App\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TrialController extends Controller
{
    public function showWords(Request $request)
    {
        $filename = $request->get('filename');
        $curPage = $request->get('cur_page') ?: 0;
        $toPage = $request->get('to_page') ?: 1;
        $take = $request->get('take') ?: 100;

        $cacheKey = $this->wordsCacheKey($filename);

        if (!Redis::EXISTS($cacheKey))
        {
            $this->writeCacheFromFile($filename);
        }

        $start = ($toPage - 1) * $take;
        $count = ($toPage - $curPage) * $take;
        $data = Redis::LRANGE($cacheKey, $start, $start + $count);
        $total = Redis::LLEN($cacheKey);

        return $this->resOK([
            'result' => $data,
            'total' => $total,
            'no_more' => !count($data)
        ]);
    }

    public function addWords(Request $request)
    {
        $user = $request->user();
        if ($user->cant('change_black_words'))
        {
            return $this->resErrRole();
        }

        $words = $request->get('words') ?: [];
        $filename = $request->get('filename');

        if (!Redis::EXISTS($this->wordsCacheKey($filename)))
        {
            $this->writeCacheFromFile($filename);
        }

        if (count($words))
        {
            Redis::LPUSH($this->wordsCacheKey($filename), $words);
        }
        $this->changeBlackWordsFile($filename);

        return $this->resNoContent();
    }

    public function deleteWords(Request $request)
    {
        $user = $request->user();
        if ($user->cant('change_black_words'))
        {
            return $this->resErrRole();
        }

        $words = $request->get('words');
        $filename = $request->get('filename');

        if (!Redis::EXISTS($this->wordsCacheKey($filename)))
        {
            $this->writeCacheFromFile($filename);
        }

        foreach ($words as $item)
        {
            if ($item)
            {
                Redis::LREM($this->wordsCacheKey($filename), 0, $item);
            }
            Redis::LREM('blocked-risk-words', 0, $item);
        }
        $this->changeBlackWordsFile($filename);

        return $this->resNoContent();
    }

    public function getBlockedWords(Request $request)
    {
        $data = Redis::LRANGE('blocked-risk-words', 0, -1);

        return $this->resOK($data);
    }

    public function clearBlockedWords(Request $request)
    {
        Redis::DEL('blocked-risk-words');

        return $this->resNoContent();
    }

    public function textTest(Request $request)
    {
        $content = $request->get('text');
        if (!$content)
        {
            return $this->resErrBad();
        }

        $wordFilter = new WordsFilter();

        return $this->resOK($wordFilter->filter($content));
    }

    public function imageTest(Request $request)
    {
        $imageUrl = $request->get('url');
        if (!$imageUrl)
        {
            return $this->resErrBad();
        }

        $imageFilter = new ImageFilter();

        return $this->resOK($imageFilter->test($imageUrl));
    }

    protected function changeBlackWordsFile($filename)
    {
        $path = base_path() . '/storage/app/' . $filename . '.txt';
        $cache = Redis::LRANGE($this->wordsCacheKey($filename), 0, -1);

        if (empty($cache))
        {
            $this->writeCacheFromFile($filename);
        }
        else
        {
            $this->writeWordsToFile($cache, $path);
        }

        $resTrie = trie_filter_new();
        $fp = fopen($path, 'r');
        if ( ! $fp)
        {
            return;
        }

        while ( ! feof($fp))
        {
            $word = fgets($fp, 1024);
            if ( ! empty($word))
            {
                trie_filter_store($resTrie, $word);
            }
        }

        trie_filter_save($resTrie,  base_path() . '/app/Services/Trial/' . $filename . '.tree');
    }

    protected function writeCacheFromFile($filename)
    {
        $path = base_path() . '/storage/app/' . $filename . '.txt';
        $words = $this->getWordsFromFile($path);
        Redis::RPUSH($this->wordsCacheKey($filename), $words);
    }

    protected function wordsCacheKey($filename)
    {
        return 'blackwords_' . $filename;
    }

    protected function getWordsFromFile($path)
    {
        if (!file_exists($path))
        {
            return [];
        }

        $fp = fopen($path, 'r');
        $words = [];
        while( ! feof($fp))
        {
            if ($line = rtrim(fgets($fp))) {
                $words[] = $line;
            }
        }
        fclose($fp);

        return $words;
    }

    protected function writeWordsToFile($words, $path)
    {
        $fp = fopen($path, 'w');

        if ( ! $fp)
        {
            return;
        }

        foreach ($words as $v)
        {
            fwrite($fp, "$v\r\n");
        }
        fclose($fp);
    }
}
