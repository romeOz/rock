<?php
namespace rock\helpers;

use rock\base\Arrayable;
use rock\base\ClassName;
use rock\execute\Exception;
use rock\request\RequestInterface;
use rock\request\SanitizeInterface;

/**
 * Helper "Sanitize"
 * @package rock\sanitize
 */
class BaseSanitize implements SanitizeInterface, RequestInterface
{
    use ClassName;

    public static $enNoiseWords = "about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,
				  				  	 being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,
				  				  	 got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,
			      				  	 make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,
				  				  	 our,out,over,said,same,see,should,since,some,still,such,take,than,that,
				  				  	 the,their,them,then,there,these,they,this,those,through,to,too,under,up,
				  				  	 very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,
				  				  	 b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";

    public static $allowedTags	  = "<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>";
    // Customer filter methods
    protected static $filters = [];

    public static $excludeStringFunctions = ['trim'];

    public static $defaultFilters = [self::STRIP_TAGS, 'trim'];

    /**
     * Adds a custom filter using a callback function
     *
     * @access public
     * @param string   $filter
     * @param callable $callback
     * @throws \Exception
     * @return bool
     */
    public static function addFilter($filter, $callback)
    {
        $method = $filter;
        if(method_exists(__CLASS__, $method) || isset(static::$filters[$filter])) {
            throw new \Exception("Filter rule '$filter' already exists.");
        }

        static::$filters[$filter] = $callback;

        return true;
    }

    /**
     * Sanitize value or array.
     *
     * @param mixed  $input
     * @param mixed $filters
     * @return mixed
     * @throws Exception
     */
    public static function sanitize($input, $filters = null)
    {
        if (is_scalar($input) || is_array($input)) {
            $isArray = is_array($input);
            $input = (array)$input;
            $filters = static::calculateFilters($input, $filters);
            $result = static::prepare($input, $filters);
            return $isArray === true ? $result : current($result);
        } elseif ($input instanceof Arrayable) {
            $filters = static::calculateFilters($input->toArray(), $filters);
            return static::prepare($input, $filters);
        }
        return $input;
    }

    protected static function calculateFilters($input, $filters)
    {
        if (empty($filters)) {
            return array_fill_keys(array_keys($input), static::$defaultFilters);
        } elseif (!is_array(current($filters))) {
            return array_fill_keys(array_keys($input), $filters);
        }

        return $filters;
    }

    /**
     * Filter the input data according to the specified filter set.
     *
     * @param  mixed $input
     * @param  array $pullFilters
     * @throws \Exception
     * @return mixed
     */
    protected static function prepare($input, array $pullFilters)
    {
        $pullFilters = static::prepareFilters($input, $pullFilters);
        foreach($pullFilters as $field => $filters) {

            if (!strstr($field, '.') || (!$value = ArrayHelper::getValue($input, $field))) {

                if(!array_key_exists($field, $input instanceof Arrayable ? $input->toArray() : $input)) {
                    continue;
                }
                $value = $input[$field];
            }

            foreach($filters as $filter) {

                $params = [];
                if (is_object($filter) && !$filter instanceof \Closure) {
                    list($filter, $params) = (array)$filter;
                }

                if(is_string($filter) && method_exists(static::className(), $filter)) {
                    $value = static::prepareByMethod($value, $filter, $params);
                } elseif ($filter instanceof \Closure || function_exists($filter)){
                    if (static::excludeStringFunction($filter, $value)) {
                        continue;
                    }
                    $value = static::prepareByClosureOrFunction($value, $filter, $params);
                } elseif (isset(static::$filters[$filter])) {
                    $value = static::prepareByCustomFilter($value, $filter, $params);
                } else {
                    throw new Exception(Exception::ERROR, "Filter method '$filter' does not exist.");
                }
            }

            $input = static::setValue($input, explode('.',$field), $value);
        }

        return $input;
    }

    /**
     * Set value
     * @param object $object
     * @param array  $keys
     * @param mixed  $value
     * @param bool  $throwException
     * @return object
     * @throws ObjectHelperException
     */
    protected static function setValue($object, array $keys, $value = null, $throwException = false)
    {
        if (count($keys) > 1) {
            $property = array_shift($keys);
            if (!isset($object[$property]) && $throwException === true) {
                throw new ObjectHelperException(
                    ObjectHelperException::CRITICAL, ObjectHelperException::SETTING_UNKNOWN_PROPERTY, [
                                                       'class' => get_class(
                                                           $object
                                                       ), 'property' => $property
                                                   ]
                );
            } else {

            }
            $object[$property] = static::setValue($object[$property], $keys, $value);
        } else {
            $property = array_shift($keys);
            if (!isset($object[$property]) && $throwException === true) {
                throw new ObjectHelperException(
                    ObjectHelperException::CRITICAL, ObjectHelperException::SETTING_UNKNOWN_PROPERTY, [
                                                       'class' => get_class(
                                                           $object
                                                       ), 'property' => $property
                                                   ]
                );
            } /*else {
                $object[$property] = [];
            }*/

            $object[$property] = $value;
        }

        return $object;
    }

    protected static function prepareByMethod($value, $filter, $params)
    {
       return is_array($value) || $value instanceof \ArrayAccess
            ? static::mapRecursive(
               $value,
               function($value) use ($filter, $params){
                   return static::$filter($value, $params);
               }
            )
            : static::$filter($value, $params);
    }

    protected static function prepareByClosureOrFunction($value, $filter, $params)
    {
        return is_array($value) || $value instanceof \ArrayAccess
            ? static::mapRecursive(
                $value,
                function($value, $key) use ($filter, $params){
                    if (static::excludeStringFunction($filter, $value)) {
                        return $value;
                    }
                    return $filter instanceof \Closure
                        ? call_user_func($filter, $value, $key, $params)
                        : call_user_func_array($filter, array_merge([$value], $params));
                }
            )
            : call_user_func_array($filter, array_merge([$value], $params));
    }


    protected static function prepareByCustomFilter($value, $filter, $params)
    {
        return is_array($value) || $value instanceof \ArrayAccess
            ? static::mapRecursive(
                $value,
                function($value) use ($filter, $params){
                    return call_user_func(static::$filters[$filter], $value, $params);
                }
            )
            : call_user_func(static::$filters[$filter], $value, $params);
    }

    protected static function mapRecursive($value, \Closure $callback)
    {
        return ArrayHelper::map($value, $callback, true);
    }

    protected static function excludeStringFunction($filter, $value)
    {
        if (!$filter instanceof \Closure &&
            array_key_exists($filter, array_flip(static::$excludeStringFunctions)) &&
            (!is_string($value) xor is_array($value))) {

            return true;
        }

        return false;
    }

    protected static function prepareFilters($input, array $filters)
    {
        if (!isset($filters[self::ANY])) {
            return $filters;
        }
        $filtersAny = $filters[self::ANY];
        unset($filters[self::ANY]);
        $fields = $input instanceof Arrayable ? array_keys($input->toArray()) : array_keys($input);
        foreach ($fields as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = array_merge($filtersAny, $filters[$field]);
                continue;
            }

            $filters[$field] = $filtersAny;
        }

        return $filters;
    }


    /**
     * Replace noise words in a string (http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm)
     *
     *
     * @param  string $value
     * @return string
     */
    protected static function noiseWords($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $value = preg_replace('/\s\s+/u', chr(32),$value);

        $value = " $value ";

        $words = explode(',', self::$enNoiseWords);

        foreach($words as $word)
        {
            $word = trim($word);

            $word = " $word "; // Normalize

            if(stripos($value, $word) !== FALSE)
            {
                $value = str_ireplace($word, chr(32), $value);
            }
        }

        return trim($value);
    }



    /**
     * Remove all known punctuation from a string
     *
     *
     * @param  string $value
     * @return string
     */
    protected static function removePunctuation($value)
    {
        return is_string($value) ? preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value) : $value;
    }

    /**
     * Sanitize the string by removing any script tags
     *
     * Usage: '<index>' => 'sanitize_string'
     *
     * @param  string $value
     * @return string
     */
    protected static function stripTags($value)
    {
        return is_string($value) ? filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) : $value;
    }

    protected static function stripScript($value)
    {
        return is_string($value) ? preg_replace(
            [
                '/\<[\\s\/]*
                (?:applet|b(?:ase|gsound|link)|
                embed|frame(?:set)?|
                i(?:frame|layer)|
                l(?:ayer|ink)|
                meta|s(?:cript|tyle)|title|xml)
                [^\>]*+\>/iusx',
                /* XSS injection IE */
                '/(\<[^\>]+?.*?)
                (?:expression|behaviour|javascript|s\\s*c\\s*r\\s*i\\s*p\\s*t\\s*)\\s*\(*(?:[^\(\)]++|\((?!\()|\)(?!\))|(?R))*\)*	# cut
                (.*?\>)/iux'
            ],
            ["", '$1$2'],
            $value
        ) : $value;
    }

    /**
     * Filter out all HTML tags except the defined basic tags
     *
     * @param  string $value
     * @param null    $params
     * @return string
     */
    protected static function basicTags($value, $params = null)
    {
        return is_string($value) ?  strip_tags($value, Helper::getValue($params['allowed'], static::$allowedTags)) : $value;
    }

    protected static function unserialize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return Serialize::unserialize($value, false);
    }

    /**
     * Sanitize the string by urlencoding characters
     *
     * Usage: '<index>' => 'urlencode'
     *
     * @param  string $value
     * @return string
     */
    protected static function urlencode($value)
    {
        return is_string($value) ? filter_var($value, FILTER_SANITIZE_ENCODED) : $value;
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities
     *
     * @param  string $value
     * @return string
     */
    protected static function htmlencode($value)
    {
        return is_string($value) ? filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS) : $value;
    }

    /**
     * Sanitize the string by removing illegal characters from emails
     *
     * @param  string $value
     * @return string
     */
    protected static function email($value)
    {
        return is_string($value) ? filter_var($value, FILTER_SANITIZE_EMAIL) : $value;
    }

    /**
     * Sanitize the string by removing illegal characters from numbers
     *
     * @param  string $value
     * @return string
     */
    protected static function numbers($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }


    protected static function positive($value)
    {
        $value = Numeric::toNumeric($value);
        return  $value < 0 ? 0 : $value;
    }

    protected static function toType($value)
    {
        return Helper::toType($value);
    }
}