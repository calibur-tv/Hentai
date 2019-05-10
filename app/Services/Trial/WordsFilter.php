<?php

namespace App\Services\Trial;

class WordsFilter
{
    protected static $BAD_WORDS;

    public function count($text)
    {
        if (app()->environment('local') || ! $text)
        {
            return 0;
        }

        if ( ! $this::$BAD_WORDS)
        {
            $this::$BAD_WORDS = trie_filter_load(__DIR__ . '/blackword.tree');
        }

        $arrRet = trie_filter_search_all($this::$BAD_WORDS, $text);

        $words = [];
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }

        return count(array_unique($words));
    }

    function filter($text)
    {
        if (app()->environment('local') || empty($text))
        {
            return [
                'text' => '',
                'filters' => []
            ];
        }

        if ( ! $this::$BAD_WORDS)
        {
            $this::$BAD_WORDS = trie_filter_load(__DIR__ . "/blackword.tree");
        }

        if (gettype($text) !== 'string')
        {
            $text = $text['text'];
        }

        $arrRet = trie_filter_search_all($this::$BAD_WORDS, $text);

        $words = array();
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }

        for ($k = 0; $k < count($words); $k++)
        {
            $text = str_replace($words[$k], ('<font color="red"><b>' . $words[$k] . '</b></font>'), $text);
        }

        return [
            'text' => $text,
            'filters' => array_unique($words)
        ];
    }
}
