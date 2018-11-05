<?php

namespace Jeason\DFA;

class Sensitive
{
    /**
     * 替换码
     * @var string
     */
    private $replaceCode = '*';

    private $maps = [];
    /**
     * 干扰因子集合
     * @var array
     */
    private $disturbList = [];
    private static $_instance = null;

    public static $badWordList = null;


    public function interference($disturbList = [])
    {
        $this->disturbList = $disturbList;
    }

    public function getHashMap()
    {
        echo json_encode($this->maps, JSON_UNESCAPED_UNICODE);
    }

    public static function init()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 干扰因子检测
     * @param $word
     * @return bool
     */
    private function checkDisturb($word)
    {
        return in_array($word, $this->disturbList);
    }

    /**
     * 使用yield生成器
     * @param $filename
     * @return \Generator
     * @throws \Exception
     */
    protected function getGeneretor($filename)
    {
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new \Exception('read file failed');
        }
        while (!feof($handle)) {
            yield fgets($handle);
        }
        fclose($handle);
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function addWords($filename)
    {
        foreach ($this->getGeneretor($filename) as $words) {
            $this->addWord(trim($words));
        }
    }

    /**
     * @param $words
     */
    public function addWord($words)
    {
        $_maps = &$this->maps;
        $len = mb_strlen($words, 'utf-8');
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($words, $i, 1, 'utf-8');
            if (isset($_maps[$word])) {
                if ($i === ($len - 1)) {
                    $_maps[$word]['end'] = 1;
                }
            } else {
                if ($i === ($len - 1)) {
                    $_maps[$word]['end'] = 1;
                }
            }
            $_maps = &$_maps[$word];
        }
    }

    public function searchKey($strWord)
    {
        $len = mb_strlen($strWord, 'utf-8');
        $arrHashMap = $this->maps;
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($strWord, $i, 1, 'utf-8');
            if (!isset($arrHashMap[$word])) {
                $arrHashMap = $this->maps;
                continue;
            }
            if ($arrHashMap[$word]['end']) {
                return true;
            }
            $arrHashMap = $arrHashMap[$word];
        }
        return false;
    }

    public function filter($strWord)
    {
        $len = mb_strlen($strWord, 'utf-8');
        $arrHashMap = $this->maps;
        $start = 0;
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($strWord, $i, 1, 'utf-8');
            if ($this->checkDisturb($word)) {
                continue;
            }
            if (!isset($arrHashMap[$word])) {
                $arrHashMap = $this->maps;
                $start = $i + 1;
                continue;
            }
            $next_word = mb_substr($strWord, $i + 1, 1, 'utf-8');
            if ($this->checkDisturb($next_word)) {
                $arrHashMap = $arrHashMap[$word];
                continue;
            }
            if ($arrHashMap[$word]['end']) {
                static::$badWordList[] = mb_substr($strWord, $start, $i - $start + 1, 'utf-8');
                $arrHashMap = $this->maps;
                $start = $i + 1;
            } else {
                if (!isset($arrHashMap[$word][$next_word])) {
                    $arrHashMap = $this->maps;
                    $start = $i + 1;
                } else {
                    $arrHashMap = $arrHashMap[$word];
                }
            }
        }
        return $this->replace($strWord, 2);
    }

    public function replace($strWord, $replaceType = 1)
    {
        foreach (self::$badWordList as $badWord) {
            if ($replaceType !== 1) {
                $replaceStr = str_repeat($this->replaceCode, mb_strlen($badWord, 'utf-8'));
            } else {
                $replaceStr = $this->replaceCode;
            }
            $strWord = str_replace($badWord, $replaceStr, $strWord);
        }
        return $strWord;
    }
}