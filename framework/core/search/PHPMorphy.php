<?php
namespace rock\search;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\exception\BaseException;
use rock\Rock;
use rock\template\Template;

class PHPMorphy implements ObjectInterface
{
    use ObjectTrait;
    /**
     * @var \phpMorphy
     */
    protected static $morphy;
    public $tpl = '@common.views\elements\highlight-yellow';
    /** @var  Template */
    protected $template;


    public function init()
    {
        if (!isset(static::$morphy)) {
            try {
                $dictBundle    = new \phpMorphy_FilesBundle(Rock::getAlias('@extensions/phpmorphy/dicts'), 'rus');
                static::$morphy = new \phpMorphy(
                    $dictBundle,
                    [
                        'storage'           => PHPMORPHY_STORAGE_FILE,
                        'with_gramtab'      => false,
                        'predict_by_suffix' => true,
                        'predict_by_db'     => true
                    ]
                );
            } catch (\Exception $e) {
                throw new BaseException($e->getMessage(), [], $e);
            }
        }
        $this->template = Container::load('template');
    }


    /**
     * Formatting query search (splits into words with morphology)
     *
     * @param string $query - query search
     * @return string|null
     */
    public function calculateQuery($query)
    {
        if (empty($query)) {
            return null;
        }
        $words = preg_replace(['/\[.*\]/isu', '/[^\w\x7F-\xFF\s]/i'], "", trim($query));
        $words = preg_replace('/ +/', ' ', $words); /* убираем двойные пробелы */
        //preg_match_all('/[a-zA-Z]+/iu',mb_strtoupper($words, CHARSET),$words_latin);
        //$words_latin = (is_array($words_latin) && count($words_latin) > 0) ? ' '.implode(' ', $words_latin[0]) : '';
        $words = preg_split('/\s|[,.:;!?"\'()]/', $words, -1, PREG_SPLIT_NO_EMPTY);
        $bulkWords = [];
        foreach ($words as $res) {
            if (mb_strlen($res, Rock::$app->charset) > 2) {
                $bulkWords[] = mb_strtoupper($res, Rock::$app->charset);
            }
        }
        //$this->_Morphy->getEncoding();
        $baseForm = static::$morphy->getBaseForm($bulkWords);
        if (is_array($baseForm) && count($baseForm)) {
            $dataWords = [];
            foreach ($baseForm as $key => $arr_res) {
                if (is_array($arr_res)) {
                    foreach ($arr_res as $val_res) {
                        if (mb_strlen($val_res, Rock::$app->charset) > 2) {
                            $dataWords[$val_res] = 1;
                        }
                    }
                    /* те слова, что отсутсвуют в словаре */
                } else {
                    if (!empty($res) && mb_strlen($res, Rock::$app->charset) > 2) {
                        $dataWords[$key] = 1;
                    }
                }
            }
            $words = implode(' ', array_keys($dataWords));
        }

        return $words;
    }

    protected static $content = [];
    
    
    /**
     * Get word forms
     *
     * @param string $content
     * @return array
     */
    public function getWords($content)
    {
        if (empty($content)) {
            return null;
        }
        /**
         * optimization (Lazy loading)
         */
        $hash = md5($content);
        if (isset(static::$content[$hash])) {
            return static::$content[$hash];
        }
        $content = preg_replace(
            ['/\[.*\]/isu', '/[^\w\x7F-\xFF\s]/isu', '/[\«\»\d]+/iu'],
            "",
            trim(strip_tags($content))
        );
        /**
         * trim twice spaces
         */
        $content = preg_replace('/ +/u', ' ', $content);
        //preg_match_all('/[a-zA-Z]+/iu',mb_strtoupper($str, CHARSET),$words_latin);
        //$words_latin = (is_array($words_latin) && count($words_latin) > 0) ? ' '.implode(' ', $words_latin[0]) : '';
        $words = preg_split('/\s|[,.:;!?"\'()]/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $bulk_words = [];
        foreach ($words as $res_words) {
            if (mb_strlen($res_words, Rock::$app->charset) > 2) {
                $bulk_words[] = mb_strtoupper($res_words, Rock::$app->charset);
            }
        }

        return static::$content[$hash] = static::$morphy->getAllForms($bulk_words);
        //return $res.$words_latin;
    }


    /**
     * Highlight words
     *
     * @param string $query - query of search
     * @param string $content      - content
     * @return string
     */
    public function highlight($query, $content)
    {
        if (empty($query) || empty($content) || empty($this->tpl)) {
            return $content;
        }
        $highlightWords = [];
        if ((!$words = $this->getWords($query)) || !is_array($words)) {
            return $content;
        }
        foreach ($words as $key => $res_words) {
            if (!$res_words) {
                $highlightWords[] = '/\b(' . $key . ')\b/isu';
            } else {
                foreach ($res_words as $res) {
                    $highlightWords[] = '/\b(' . $res . ')\b/isu';
                }
            }
        }

        return preg_replace(
            array_reverse($highlightWords),
            $this->template->replaceByPrefix($this->tpl),
            $content
        );
    }
}