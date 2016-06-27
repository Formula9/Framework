<?php namespace Nine\Library;

/**
 * Strings is a compendium of string functions, supplied as static methods,
 * collected from a number of OSS sources or created for the project.
 *
 * Several methods are included for compatibility (often as pseudonyms)
 * with imported or included packages.
 *
 * @package Nine Library
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

trait Strings
{
    /**
     * **Converts a CamelCase string to underscore_case.**
     *
     * ie:  CamelCase -> camel_case
     *      MyCAMELCase -> my_camel_case
     *      etc.
     *
     * @param        $camel_case_string
     * @param string $delimiter
     *
     * @return string
     * This code was posted by <a href='http://stackoverflow.com/users/18393/cletus'>cletus</a>
     * on Stack Overflow.
     */
    public static function camel_to_snake($camel_case_string, $delimiter = '_')
    {
        preg_match_all(
            '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!',
            $camel_case_string,
            $matches
        );
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode($delimiter, $ret);
    }

    /**
     * **A simple readable json encoding utility.**
     *
     * @author   bohwaz <http://php.net/manual/en/function.json-encode.php#102091>
     *
     * @param     $data
     * @param int $indent
     *
     * @return string
     */
    public static function encode_readable_json($data, $indent = 0)
    {
        $_escape = function ($str) {
            return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
        };

        $out = '';

        foreach ($data as $key => $value) {
            //$out .= str_repeat("\t", $indent + 1);
            $out .= str_repeat("\t", $indent + 1) . "\"" . $_escape((string) $key) . "\": ";

            if (is_object($value) || is_array($value)) {
                $out .= "\n";
                $out .= self::encode_readable_json($value, $indent + 1);
            }
            elseif (is_bool($value)) {
                $out .= $value ? 'true' : 'false';
            }
            elseif (is_null($value)) {
                $out .= 'null';
            }
            elseif (is_string($value)) {
                $out .= "\"" . $_escape($value) . "\"";
            }
            else {
                $out .= $value;
            }

            $out .= ",\n";
        }

        if ($out !== '') {
            $out = substr($out, 0, -2);
        }

        $out = str_repeat("\t", $indent) . "{\n" . $out;
        $out .= "\n" . str_repeat("\t", $indent) . '}';

        return $out;
    }

    /**
     * **Determines if a string ends with another string.**
     *
     * @param string $substring
     * @param string $string
     *
     * @return bool
     */
    public static function ends_with($substring, $string)
    {
        // search forward starting from end minus needle length characters
        return
            ($substring === '')
            or (($temp = strlen($string) - strlen($substring)) >= 0
                and strpos($string, $substring, $temp) !== FALSE);
    }

    /**
     * **Returns a string converted with `htmlentities`.**
     *
     * @param  string $value
     *
     * @return string
     */
    public static function entities($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', FALSE);
    }

    /**
     * **Shortcut to `htmlspecialchars`. UTF-8 aware.**
     *
     * <pre>example:
     *      given:  "<a href='/home/test'>Home Test</a>"
     *
     *      result: "&lt;a href='/home/test'&gt;Home Test&lt;/a&gt;"</pre>
     *
     * @param      $string
     * @param bool $double_encode
     *
     * @return string
     */
    public static function hsc($string, $double_encode = TRUE)
    {
        return htmlspecialchars($string, ENT_COMPAT | ENT_HTML5, 'UTF-8', $double_encode);
    }

    /**
     * **Left-pad a string.**
     *
     * @param string $str
     * @param int    $length
     * @param string $space
     *
     * @return string
     */
    public static function pad_left($str, $length = 0, $space = ' ')
    {
        return str_pad($str, $length, $space, STR_PAD_LEFT);
    }

    /**
     * **Determine if a given string matches a regular expression.**
     *
     * @param  string $value
     *
     * @param  string $pattern
     *
     * @return bool
     */
    public static function pattern_matches($value, $pattern)
    {
        if ($pattern === $value) {
            return TRUE;
        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern) . '\z';

        return (bool) preg_match('#^' . $pattern . '#', $value);
    }

    /**
     * **Removes standard quotes from a string. Is not multi-byte-aware.**
     *
     * @param $string
     *
     * @return mixed
     */
    public static function remove_quotes($string)
    {
        return str_replace(['"', "'"], '', $string);
    }

    /**
     * **Transforms a slug string into a title string.**
     *
     * The resulting string will have every word capitalized.
     *
     * @param string $slug
     *
     * @return string
     */
    public static function slug_to_title($slug)
    {
        return ucwords(str_replace('-', ' ', $slug));
    }

    /**
     * **Converts a underscore_case string to standard or identifier CamelCase.**
     *
     *      Standard CamelCase capitalizes all resulting words.
     *      Identifier CamelCase capitalizes all but the first word.
     *
     *  <pre>
     *  example 1:
     *      call:   snake_to_camel('open_the_door_sally', true)
     *      result: "openTheDoorSally"
     *
     *  example 2:
     *      call:   snake_to_camel('open_the_door_sally')
     *      result: "OpenTheDoorSally"</pre>
     *
     * @param string $string        The string to be converted.
     * @param bool   $asIdentifier  If TRUE then the first word will be all in lower case.<br>
     *                              If FALSE then all words will be capitalized.
     *
     * @return string converted string
     */
    public static function snake_to_camel($string, $asIdentifier = FALSE)
    {
        $string = ucwords(str_replace(['_', '.'], ' ', $string));

        return $asIdentifier ? lcfirst(str_replace(' ', '', $string)) : str_replace(' ', '', $string);
    }

    /**
     * **Converts underscores to spaces and capitalizes first letter of each word**
     *
     * <pre>example:
     *      call:   snake_to_text('it_was_the_winter_of_my_discontent.')
     *
     *      result: "It Was The Winter Of My Discontent."</pre>
     *
     * @param string $word
     * @param string $space
     *
     * @return string
     */
    public static function snake_to_heading($word, $space = ' ')
    {
        $prep = ucwords(str_replace('_', ' ', $word));

        return ucwords(str_replace(' ', $space, $prep));
    }

    /**
     * **Determines whether a string begins with a substring.**
     *
     * @param string|array $substring
     * @param string       $string
     *
     * @return bool
     */
    public static function starts_with($substring, $string)
    {
        foreach ((array) $substring as $needle) {
            if ($needle !== '' && strpos($string, $needle) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * **Tests whether a substring exists in a string**
     *
     * @param string $substring
     * @param string $string
     *
     * @return bool
     */
    public static function str_has($substring, $string)
    {
        foreach ((array) $substring as $needle)
            if ($needle !== '' && strpos($string, $needle) !== FALSE) {
                return TRUE;
            }

        return FALSE;
    }

    /**
     * **Determine if a given string matches a given regular expression pattern.**
     *
     * @param  string $pattern
     * @param  string $value
     *
     * @return bool
     */
    public static function str_matches($pattern, $value)
    {
        if ($pattern === $value) {
            return TRUE;
        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern) . '\z';

        return (bool) preg_match('#^' . $pattern . '#', $value);
    }

    /**
     * **Format a given string to valid URL string.**
     *
     * Returns a string suitable for a uri or a slug.
     * <pre>
     * example:
     * call:    var_export(str_to_uri('An Article Title'));
     *
     * result:  'an-article-title'</pre>
     *
     * @param string $string
     *
     * @return string URL-safe string
     */
    public static function string_to_uri($string)
    {
        // Allow only alphanumerics, underscores and dashes
        $string = preg_replace('/([^a-zA-Z0-9_\-]+)/', '-', strtolower($string));

        // Replace extra spaces and dashes with single dash
        $string = preg_replace('/\s+/', '-', $string);
        $string = preg_replace('|-+|', '-', $string);

        // Trim extra dashes from beginning and end
        $string = trim($string, '-');

        return $string;
    }

    /**
     * **Strips characters off the end of a string.**
     *
     * @param $characters
     * @param $string
     *
     * @return string
     */
    public static function strip_tail($characters, $string)
    {
        return self::ends_with($characters, $string) ? rtrim($string, $characters) : $string;
    }

    /**
     * **Truncates a string to a certain length and adds an ellipse to the tail.**
     *
     * @param        $string
     * @param string $endlength
     * @param string $end
     *
     * @return string
     */
    public static function truncate($string, $endlength = '30', $end = '...')
    {
        $strlen = strlen($string);
        if ($strlen > $endlength) {
            $trim = $endlength - $strlen;
            $string = substr($string, 0, $trim);
            $string .= $end;
        }

        return $string;
    }

    /**
     * **Translate a variable type to a string representation.**
     *
     * @param $var - variable to be examined.
     *
     * @return string
     */
    public static function var_to_string($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = [];
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, static::var_to_string($v));
            }

            return sprintf('Array(%s)', implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (NULL === $var) {
            return 'null';
        }

        if (FALSE === $var) {
            return 'false';
        }

        if (TRUE === $var) {
            return 'true';
        }

        return (string) $var;
    }
}
