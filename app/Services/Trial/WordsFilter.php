<?php

namespace App\Services\Trial;

class WordsFilter
{
    protected static $BAD_WORDS_LEVEL_1;
    protected static $BAD_WORDS_LEVEL_2;
    protected $delete_line = 1;
    protected $review_line = 3;

    public function count($text)
    {
        if ( ! $text)
        {
            return 0;
        }

        $this->loadWords();
        $words = [];

        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_1, $text);
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }
        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_2, $text);
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }

        return count(array_unique($words));
    }

    public function check($text)
    {
        if ( ! $text)
        {
            return [
                'review' => false,
                'delete' => false
            ];
        }

        $this->loadWords();
        $words = [];
        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_2, $text);
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }
        if (count($words) >= $this->delete_line)
        {
            return [
                'review' => false,
                'delete' => true
            ];
        }

        $words = [];
        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_1, $text);
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }

        if (count($words) >= $this->review_line)
        {
            return [
                'review' => true,
                'delete' => false
            ];
        }

        return [
            'review' => false,
            'delete' => false
        ];
    }

    public function filter($text)
    {
        if (!$text)
        {
            return [
                'text' => '',
                'words' => []
            ];
        }

        $this->loadWords();
        $words = [];

        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_1, $text);
        for ($k = 0; $k < count($arrRet); $k++)
        {
            $words[] = substr($text, $arrRet[$k][0], $arrRet[$k][1]);
        }
        $arrRet = trie_filter_search_all($this::$BAD_WORDS_LEVEL_2, $text);
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
            'words' => array_unique($words)
        ];
    }

    protected function loadWords()
    {
        if ( ! $this::$BAD_WORDS_LEVEL_1)
        {
            $this::$BAD_WORDS_LEVEL_1 = trie_filter_load(__DIR__ . '/words_level_1.tree');
        }

        if ( ! $this::$BAD_WORDS_LEVEL_2)
        {
            $this::$BAD_WORDS_LEVEL_2 = trie_filter_load(__DIR__ . '/words_level_2.tree');
        }
    }
}
