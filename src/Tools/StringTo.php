<?php

namespace SITE\Tools;

class StringTo
{
    private static $decimal_point       = ',';
    private static $thousands_seperator = '';
    private static $decimals            = 2;

    public static function setPriceFormat($decimal_point, $thousands_seperator = '', $decimals = 2)
    {
        self::$decimal_point       = !empty($decimal_point) ? $decimal_point : ',';
        self::$thousands_seperator = !is_null($thousands_seperator) ? $thousands_seperator : '';
        self::$decimals            = !is_null($decimals) ? $decimals : 2;
    }

    public static function getPriceFormat()
    {
        return [
            'decimal_point'       => self::$decimal_point,
            'thousands_seperator' => self::$thousands_seperator,
            'decimals'            => self::$decimals,
        ];
    }

    /**
     * float() function.
     *
     * G?r str?ng till floatVal
     *
     * @param mixed $string
     * @param mixed $how
     * @param mixed $null_value
     */
    public static function sanitize($string = null, $how = 'string', $null_value = true)
    {
        if (in_array($how, ['int', 'float', 'xss'])) {
            $null_value = false;
        }
        if (!$string && $null_value) {
            return null;
        }

        if (is_array($string)) {
            array_walk_recursive($string, function (&$v) use ($how, $null_value) {
                $v = self::sanitize($v, $how, $null_value);
            });
        } else {
            switch ($how) {
                case 'string':
                    $string = filter_var($string, FILTER_SANITIZE_STRING);
                    $string = htmlspecialchars($string, ENT_QUOTES, 'ISO-8859-1');

                    break;
                case 'int':
                    $string = (int) filter_var($string, FILTER_SANITIZE_NUMBER_INT);

                    break;
                case 'float':
                    $string = (float) filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT);

                    break;
                case 'email':
                    $string = (string) filter_var($string, FILTER_SANITIZE_EMAIL);

                    break;
                case 'url':
                    $string = (string) filter_var($string, FILTER_SANITIZE_URL);

                    break;
                case 'xss':
                    $string = htmlspecialchars($string, ENT_QUOTES, 'ISO-8859-1');

                    break;
                case 'filename':
                    $trans_array = [
                        '?' => 'a',
                        '?' => 'a',
                        '?' => 'o',
                        '?' => 'A',
                        '?' => 'A',
                        '?' => 'O',
                    ];

                    $string = strtr($string, $trans_array);
                    $string = strip_tags($string);
                    $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
                    $string = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $string);
                    $string = strtolower($string);
                    $string = html_entity_decode($string, ENT_QUOTES, 'utf-8');
                    $string = htmlentities($string, ENT_QUOTES, 'utf-8');
                    $string = preg_replace('/(&)([a-z])([a-z]+;)/i', '$2', $string);
                    $string = str_replace(' ', '_', $string);
                    $string = rawurlencode($string);
                    $string = str_replace('%', '_', $string);

                    break;
            }
        }

        // F?r att kunna anv?nda ?? n?r man rensasr
        if ($string === false && $null_value) {
            return null;
        }

        return $string;
    }

    /**
     * Genererar en str?ng med b?de key och value.
     *
     * @param mixed $array
     * @param mixed $key
     * @param mixed $each
     */
    public static function superImplode($array, $key = ',', $each = '|')
    {
        if (!is_array($array)) {
            return false;
        }

        return urldecode(str_replace('=', $key, http_build_query($array, null, $each)));
    }

    public static function float($string, $decimals = 3)
    {
        $string = trim($string);
        $string = str_replace(',', '.', $string);
        $string = preg_replace('/\.(?=.*\.)/', '', $string);
        $string = floatval($string);

        return round($string, $decimals);
    }

    public static function priceDecimal($string)
    {
        $string = trim($string);
        $string = str_replace(' ', '', $string);
        $string = str_replace(',', '.', $string);

        return round((float) $string, PRICE_DECIMALS);
    }

    public static function int($string, $decimals = 3)
    {
        $string = trim($string);
        $string = str_replace(' ', '', $string);
        $string = str_replace(',', '.', $string);

        return (int) round((int) $string);
    }

    // F?r att php ?r sjuk!! k?r man intval direkt kan det fela helt!
    public static function realInt($val, $decimals = 2)
    {
        return intval(number_format($val, 2, '', ''));
    }

    public static function priceFormat($price, $locale = 'sv') // $decimals = 2 borta 20200608
    {
        if (is_null($price) || $price === '' || $price === false) {
            return '';
        }
        if (is_numeric($price)) {
            // return number_format($price, 3, self::$decimal_point, self::$thousands_seperator);
            // 190903 denna m?ste ha samma decimaler som Rounding i price-class, annars blir det skevt!
            return number_format($price, self::$decimals, self::$decimal_point, self::$thousands_seperator);
        }

        return $price;
    }

    public static function charCut($string, $length, $suffix = '...')
    {
        if ($length >= strlen($string)) {
            return $string;
        }
        $key_length = $length - 1;
        while (ord($string[$key_length]) & 0x80) {
            --$key_length;
        }

        return substr($string, 0, $length - (($length + $key_length + 1) % 2)) . $suffix;
    }

    public static function lower($text)
    {
        $text = strtolower($text);
        $text = str_replace('?', '?', $text);
        $text = str_replace('?', '?', $text);
        $text = str_replace('?', '?', $text);

        return str_replace('?', '?', $text);
    }

    public static function lang($language, $text)
    {
        $text = preg_replace('|[<]' . $language . '[>](.*?)[<]/' . $language . '[>]|si', '$1', $text);

        return preg_replace(
            '#[<](se|no|fi|en|se_eur|no_eur|fi_sek|sek|nok|eur|dkk)[>](.*?)[<]/(se|no|fi|en|se_eur|no_eur|fi_sek|sek|nok|eur|dkk)[>]#si',
            '',
            $text
        );
    }

    public static function clean($text)
    {
        if (is_array($text)) {
            array_walk_recursive($text, function (&$v) {
                $v = self::clean($v);
            });
        } else {
            $text = trim($text);
            $text = strip_tags($text);
            $text = str_replace("'", '"', $text);
            $text = str_replace('?', '"', $text);
            $text = str_ireplace('<br>', '&nbsp;', $text);
            $text = str_ireplace('</div>', '&nbsp;', $text);
            $text = preg_replace('#\R+#', '&nbsp;', $text);
            $text = preg_replace('/^\s+|\n|\r|\s+$/m', '', $text);
            $text = stripslashes($text);
            $text = str_replace('&nbsp;', ' ', $text);
        }

        return $text;
    }

    /**
     * cleanHtml function.
     *
     * Tar bort allt on?digt och g?r en enda sammanh?ngande str?ng
     *
     * @param mixed $value
     */
    public static function cleanHtml($value)
    {
        $value = preg_replace('`[\t]+`', '', $value);                 // Ta bort tabbar
        $value = preg_replace('/(?=<!--)([\s\S]*?)-->/', '', $value); // Ta bort kommentarer
        $value = preg_replace('`[\r\n]+`', "\n", $value);             // Ta bort dubbla radbrytningar
        $value = preg_replace('`[\n]+`', '', $value);                 // Ta bort radbrytningar
        return str_replace(' />', '/>', $value);                    // Ta bort space i sluttag
    }

    // Varje f?rsta bokstav i en mening bli versal
    public static function sentenceCase($string)
    {
        $sentences  = preg_split('/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $new_string = '';
        foreach ($sentences as $key => $sentence) {
            $new_string .= ($key & 1) == 0 ? ucfirst(self::lower(trim($sentence))) : $sentence . ' ';
        }

        return trim($new_string);
    }

    // URL-v?nlig str?ng
    public static function url($url = false, $clean = true)
    {
        if (!$url || is_array($url)) {
            return false;
        }
        $url = $url . '';

        $url = strtolower($url);
        $url = trim($url);

        $url = str_replace('?', '', $url);
        $url = str_replace('&#0153;', '', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace('&#0169;', '', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace('&#0174;', '', $url);

        $url = str_replace('?', 'u', $url);
        $url = str_replace('?', 'u', $url);
        $url = str_replace('?', '?', $url);
        $url = str_replace('?', '?', $url);
        $url = str_replace('?', '?', $url);
        $url = str_replace('?', '?', $url);

        if ($clean) {
            $url = str_replace('?', 'a', $url);
            $url = str_replace('?', 'a', $url);
            $url = str_replace('?', 'a', $url);
            $url = str_replace('?', 'a', $url);
            $url = str_replace('?', 'o', $url);
            $url = str_replace('?', 'o', $url);
        } else {
            $url = str_replace('?', 'ae', $url);
            $url = str_replace('?', 'aa', $url);
            $url = str_replace('?', 'ae', $url);
            $url = str_replace('?', 'ae', $url);
            $url = str_replace('?', 'oe', $url);
            $url = str_replace('?', 'oe', $url);
        }

        $url = str_replace("\\'", '', $url); // Felescapat
        $url = str_replace('\\', '', $url); // Felescapat
        $url = str_replace('"', '', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace("'", '', $url);
        $url = str_replace('.', '-', $url);
        $url = str_replace(',', '-', $url);
        $url = str_replace(' & ', '-', $url);
        $url = str_replace(' ', '-', $url);
        $url = str_replace('/', '-', $url);
        $url = str_replace('&', '-', $url);
        $url = str_replace('|', '-', $url);
        $url = str_replace('---', '-', $url);
        $url = str_replace('--', '-', $url);
        $url = str_replace('(', '', $url);
        $url = str_replace(')', '', $url);
        $url = str_replace('+', 'plus', $url);
        $url = str_replace('?', 'e', $url);
        $url = str_replace('?', 'e', $url);
        $url = str_replace('<', '-', $url);
        $url = str_replace('>', '-', $url);
        $url = str_replace('?', '2', $url);
        $url = str_replace('__', '_', $url);
        $url = str_replace('--', '-', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace('?', 'e', $url);
        $url = str_replace(':', '', $url);
        $url = str_replace('#', '', $url);
        $url = str_replace('%', '', $url);
        $url = str_replace('!', '-', $url);
        $url = str_replace('?', 'u', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace('?', 'e', $url);
        $url = str_replace('$', '', $url);
        $url = str_replace('?', '', $url);
        $url = str_replace('?', '', $url);

        if (substr($url, -1) == '-') {
            $url = substr_replace($url, '', -1);
        }

        return $url;
    }

    public static function snakeCase($data)
    {
        if (is_array($data)) {
            return self::snakeCaseRecursive($data);
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $data));
    }

    /**
     * ?ndrar bara keyn s? man vet.
     *
     * @param array $array
     */
    private static function snakeCaseRecursive($array)
    {
        $arr = [];
        foreach ($array as $key => $value) {
            $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            if (is_array($value)) {
                $value = self::snakeCaseRecursive($value);
            }

            $arr[$key] = $value;
        }

        return $arr;
    }

    public static function repairSerializeString($value)
    {
        $regex = '/s:([0-9]+):"(.*?)"/';

        return preg_replace_callback(
            $regex,
            function ($match) {
                return 's:' . mb_strlen($match[2]) . ':"' . $match[2] . '"';
            },
            $value
        );
    }

    // En l?nk sparad med ??? funkar inte i edge, m?ste g?ras om
    public static function urlEdgeSafe(string $url)
    {
        $url_components = parse_url($url);
        if (!empty($url_components['query'])) {
            parse_str($url_components['query'], $url_query);
            // 191016 - S?klart edge/ie som skiter sig utan detta
            if (!empty($url_query['q'])) {
                $new_href = !empty($url_components['scheme']) ? $url_components['scheme'] . '://' : '';
                $new_href .= $url_components['host'] ?? '';
                $new_href .= $url_components['path'] ?? '';
                $new_href .= '?';
                $url_query['q'] = rawurlencode($url_query['q']);
                foreach ($url_query as $qk => $qv) {
                    $new_href .= $qk . '=' . $qv . '&';
                }

                return rtrim($new_href, '&');
            }
        }

        return $url;
    }

    /**
     * Likt array_column men med m?jlighet att specificera vilka kolumner man vill ha kvar.
     */
    public static function arrayColumns(array $array, array $keys, string $key = null)
    {
        if (!is_null($key)) {
            $array = array_column($array, null, $key);
        }
        $keys     = array_flip($keys);

        return array_map(function ($a) use ($keys) {
            $new_array = array_intersect_key($a, $keys);
            $result = [];
            foreach ($keys as $key => $temp) {
                $result[$key] = $new_array[$key];
            }

            return $result;
        }, $array);
    }

    /**
     * St?da bort tomma values ur en array tex en prisarray
     * loopar sig ner?t ? donar p? f?r fullt.
     *
     * @param array $price_array
     *
     * @return array
     */
    public static function cleanArrayFromEmptyValues($price_array)
    {
        foreach ($price_array as $key => $val) {
            if (is_array($val)) {
                $price_array[$key] = self::cleanArrayFromEmptyValues($val);
            } elseif (empty($val)) {
                unset($price_array[$key]);
            }
        }

        return $price_array;
    }

    public static function exists_in_array($needle, $haystack): bool
    {
        $res = false;

        foreach ($haystack as $key => $val) {
            if (is_array($val)) {
                $res = self::exists_in_array($needle, $val);
                if ($res) {
                    return true;
                }
            } elseif ($needle == $val) {
                return true;
            }
        }

        return $res;
    }

    public function unserializeBase64($string)
    {
        return unserialize(base64_decode($string));
    }

    public function serializeBase64($array)
    {
        return base64_encode(serialize($array));
    }
}