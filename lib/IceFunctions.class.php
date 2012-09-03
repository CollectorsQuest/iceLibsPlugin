<?php

if (!function_exists('http_build_url'))
{
  define('HTTP_URL_REPLACE', 1);              // Replace every part of the first URL when there's one of the second URL
  define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
  define('HTTP_URL_JOIN_QUERY', 4);           // Join query strings
  define('HTTP_URL_STRIP_USER', 8);           // Strip any user authentication information
  define('HTTP_URL_STRIP_PASS', 16);          // Strip any password authentication information
  define('HTTP_URL_STRIP_AUTH', 32);          // Strip any authentication information
  define('HTTP_URL_STRIP_PORT', 64);          // Strip explicit port numbers
  define('HTTP_URL_STRIP_PATH', 128);         // Strip complete path
  define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
  define('HTTP_URL_STRIP_FRAGMENT', 512);     // Strip any fragments (#identifier)
  define('HTTP_URL_STRIP_ALL', 1024);         // Strip anything but scheme and host
}

/**
 * This is a class to "overwrite" default PHP function to make them
 * either work on all PHP versions or to extend them in some way
 */
class IceFunctions
{
  /**
   * @static
   *
   * @param  array   $values
   * @param  string  $callback
   *
   * @return array
   */
  public static function array_filter_recursive($values, $callback = null)
  {
    foreach ($values as $key => $value)
    {
      if (is_array($value))
      {
        $values[$key] = self::array_filter_recursive($value, $callback);
      }
    }

    return array_filter($values);
  }

  /**
   * Implementation of a recursive version of PHP's array_unique
   *
   * @static
   *
   * @param  array $array
   * @return array
   */
  public static function array_unique_recursive($array)
  {
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

    foreach ($result as $key => $value)
    {
      if (is_array($value))
      {
        $result[$key] = self::array_unique_recursive($value);
      }
    }

    return $result;
  }

  /**
   * @static
   *
   * @param  array  $values
   * @return array
   */
  public static function array_power_set($values)
  {
    // Initialize by adding the empty set
    $results = array(array());

    foreach ($values as $value)
    {
      foreach ($results as $combination)
      {
        array_push($results, array_merge(array($value), $combination));
      }
    }

    return $results;
  }

  /**
   * @static
   *
   * @param  array|PropelObjectCollection  $items
   * @param  integer  $columns
   * @param  boolean  $keep_keys
   *
   * @return array
   */
  public static function array_vertical_sort($items, $columns, $keep_keys = false)
  {
    /** No need to do anything for $columns less than 2 */
    if ((int) $columns < 2)
    {
      return $items;
    }

    $sorted = array();
    $total = count($items);
    $keys = ($items instanceof PropelObjectCollection) ? range(0, $items->count() - 1) : array_keys((array) $items);

    $rowCount = ceil($total / $columns);
    for ($i = 0; $i < $rowCount * $columns; $i++)
    {
      $index = ($i % $columns) * $rowCount + floor($i / $columns);

      if ($keep_keys === true)
      {
        $key = isset($keys[$index]) ? $keys[$index] : max($keys) + 1;
        $sorted[$key] = ($index < $total) ? $items[$key] : null;
      }
      else
      {
        $sorted[] = ($index < $total) ? $items[$index] : null;
      }
    }

    return $sorted;
  }

  /**
   * @static
   *
   * @param  array    $array  Array to sort
   * @param  string   $sortby  Sort by this key
   * @param  string   $order  Sort order asc/desc (ascending or descending).
   * @param  integer  $type  Type of sorting to perform
   *
   * @return array
   */
  public static function array_key_sort($array, $sortby, $order = 'asc', $type = SORT_NUMERIC)
  {
    if (!is_array($array))
    {
      return $array;
    }

    $out = array();

    foreach ($array as $key => $val) {
      $sa[$key] = $val[$sortby];
    }

    if ($order == 'asc') {
      asort($sa, $type);
    } else {
      arsort($sa, $type);
    }

    foreach ($sa as $key => $val) {
      $out[] = $array[$key];
    }

    return $out;
  }

  /**
   * @param  array    $array
   * @param  boolean  $keep_keys
   *
   * @return array
   */
  public static function array_flatten($array, $keep_keys = false)
  {
    if (!is_array($array))
    {
      return array();
    }

    $result = array();
    foreach ($array as $key => $value)
    {
      if (is_array($value))
      {
        $result = array_merge($result, self::array_flatten($value, $keep_keys));
      }
      else if ($keep_keys == true)
      {
        $result[$key] = $value;
      }
      else
      {
        $result[] = $value;
      }
    }

    return $result;
  }

  public static function array_to_csv($data, $delimeter = ',', $enclosure = '"')
  {
    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, $data, $delimeter, $enclosure);
    rewind($stream);
    $csv = fgets($stream);
    fclose($stream);

    return $csv;
  }

  /**
   * @static
   *
   * @param  int|float  $number
   * @param  integer    $decimals
   * @param  string     $culture
   *
   * @return string
   */
  public static function number_format($number, $decimals = 0, $culture = 'bg_BG')
  {
    switch ($culture)
    {
      case 'en_US':
        $number = number_format($number, $decimals, '.', ',');
        break;
      case 'bg_BG':
      default:
        $number = number_format($number, $decimals, ',', ' ');
        break;
    }

    return $number;
  }

  /**
   * @static
   *
   * @param  string  $str1
   * @param  string  $str2
   *
   * @return int
   */
  public static function levenshtein($str1, $str2)
  {
    $str1 = mb_strtolower($str1, 'utf8');
    $str2 = mb_strtolower($str2, 'utf8');

    $len1 = mb_strlen($str1, 'utf8');
    $len2 = mb_strlen($str2, 'utf8');

    // strip common prefix
    $i = 0;
    do
    {
      if (mb_substr($str1, $i, 1, 'utf8') != mb_substr($str2, $i, 1, 'utf8'))
      {
        break;
      }

      $i++;

      $len1--;
      $len2--;
    }
    while($len1 > 0 && $len2 > 0);

    if ($i > 0)
    {
      $str1 = mb_substr($str1, $i, mb_strlen($str1, 'utf8'), 'utf8');
      $str2 = mb_substr($str2, $i, mb_strlen($str2, 'utf8'), 'utf8');
    }

    // strip common suffix
    $i = 0;
    do
    {
      if (mb_substr($str1, $len1-1, 1, 'utf8') != mb_substr($str2, $len2-1, 1, 'utf8'))
      {
        break;
      }
      $i++;
      $len1--;
      $len2--;
    }
    while($len1 > 0 && $len2 > 0);

    if ($i > 0)
    {
      $str1 = mb_substr($str1, 0, $len1, 'utf8');
      $str2 = mb_substr($str2, 0, $len2, 'utf8');
    }

    if ($len1 == 0)
    {
      return $len2;
    }
    if ($len2 == 0)
    {
      return $len1;
    }

    $v0 = range(0, $len1);
    $v1 = array();

    for ($i = 1; $i <= $len2; $i++)
    {
      $v1[0] = $i;
      $str2j = mb_substr($str2, $i - 1, 1, 'utf8');

      for ($j = 1; $j <= $len1; $j++)
      {
        $cost = (mb_substr($str1, $j - 1, 1, 'utf8') == $str2j) ? 0 : 1;

        $m_min = $v0[$j] + 1;
        $b = $v1[$j - 1] + 1;
        $c = $v0[$j - 1] + $cost;

        if ($b < $m_min)
        {
          $m_min = $b;
        }
        if ($c < $m_min)
        {
          $m_min = $c;
        }

        $v1[$j] = $m_min;
      }

      $vTmp = $v0;
      $v0 = $v1;
      $v1 = $vTmp;
    }

    return (int) @$v0[$len1];
  }

  /**
   * @static
   *
   * @param  string  $data
   * @param  string  $passwd
   * @param  string  $algo (sha1 or md5)
   *
   * @return string
   */
  public static function hmac($data, $passwd, $algo = 'sha1')
  {
    $algo = strtolower($algo);
    $p = array('md5' => 'H32', 'sha1' => 'H40');

    if (strlen($passwd) > 64)
    {
      $passwd = pack($p[$algo], $algo($passwd));
    }
    else if (strlen($passwd) < 64)
    {
      $passwd = str_pad($passwd, 64, chr(0));
    }

    $ipad = substr($passwd, 0, 64) ^ str_repeat(chr(0x36), 64);
    $opad = substr($passwd, 0, 64) ^ str_repeat(chr(0x5C), 64);

    return $algo($opad . pack($p[$algo], $algo($ipad . $data)));
  }

  /**
   * @static
   *
   * @return string
   */
  public static function gethostname()
  {
    if (version_compare(PHP_VERSION, '5.3.0') >= 0)
    {
      $host = gethostname();
    }
    else
    {
      $host = php_uname('n');
    }

    return (string) $host;
  }

  /**
   * @static
   * @param  integer $number
   * @param  integer $length
   *
   * @return string
   */
  public static function udihash($number, $length = 5)
  {
    $golden_primes = array(
      1,41,2377,147299,9132313,566201239,35104476161,2176477521929
    );

    $chars = array(
      0=>48,1=>49,2=>50,3=>51,4=>52,5=>53,6=>54,7=>55,8=>56,9=>57,10=>65,
      11=>66,12=>67,13=>68,14=>69,15=>70,16=>71,17=>72,18=>73,19=>74,20=>75,
      21=>76,22=>77,23=>78,24=>79,25=>80,26=>81,27=>82,28=>83,29=>84,30=>85,
      31=>86,32=>87,33=>88,34=>89,35=>90,36=>97,37=>98,38=>99,39=>100,40=>101,
      41=>102,42=>103,43=>104,44=>105,45=>106,46=>107,47=>108,48=>109,49=>110,
      50=>111,51=>112,52=>113,53=>114,54=>115,55=>116,56=>117,57=>118,58=>119,
      59=>120,60=>121,61=>122
    );

    // Make sure the $length is right
    if ($length > 8 || $length < 1)
    {
      $length = 5;
    }

    $ceil = pow(62, $length);
    $prime = $golden_primes[$length];
    $dec = ($number * $prime) - floor($number * $prime/$ceil) * $ceil;
    $hash = self::base62($dec, $chars);

    return str_pad($hash, $length, "0", STR_PAD_LEFT);
  }

  /**
   * @static
   * @param  integer $int
   * @param  array $chars
   *
   * @return string
   */
  private static function base62($int, $chars = array())
  {
    $key = '';

    while($int > 0)
    {
      $mod  = $int - floor($int / 62) * 62;
      $key .= chr($chars[$mod]);
      $int  = floor($int / 62);
    }

    return strrev($key);
  }

  /**
   * Build URL
   * The parts of the second URL will be merged into the first according to the flags argument.
   *
   * @param  mixed    $url      (Part(s) of) an URL in form of a string or associative array like parse_url() returns
   * @param  mixed    $parts    Same as the first argument
   * @param  integer  $flags    A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
   *
   * @return string
   */
  public static function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE)
  {
    $keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

    // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
    if ($flags & HTTP_URL_STRIP_ALL)
    {
      $flags |= HTTP_URL_STRIP_USER;
      $flags |= HTTP_URL_STRIP_PASS;
      $flags |= HTTP_URL_STRIP_PORT;
      $flags |= HTTP_URL_STRIP_PATH;
      $flags |= HTTP_URL_STRIP_QUERY;
      $flags |= HTTP_URL_STRIP_FRAGMENT;
    }
    // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
    else if ($flags & HTTP_URL_STRIP_AUTH)
    {
      $flags |= HTTP_URL_STRIP_USER;
      $flags |= HTTP_URL_STRIP_PASS;
    }

    // Parse the original URL
    $parse_url = is_string($url) ? parse_url($url) : $url;

    // Scheme and Host are always replaced
    if (isset($parts['scheme'])) {
      $parse_url['scheme'] = $parts['scheme'];
    }
    if (isset($parts['host'])) {
      $parse_url['host'] = $parts['host'];
    }

    // (If applicable) Replace the original URL with it's new parts
    if ($flags & HTTP_URL_REPLACE)
    {
      foreach ($keys as $key)
      {
        if (isset($parts[$key]))
          $parse_url[$key] = $parts[$key];
      }
    }
    else
    {
      // Join the original URL path with the new path
      if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
      {
        if (isset($parse_url['path'])) {
          $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
        } else {
          $parse_url['path'] = $parts['path'];
        }
      }

      // Join the original query string with the new query string
      if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
      {
        if (isset($parse_url['query'])) {
          $parse_url['query'] .= '&' . $parts['query'];
        } else {
          $parse_url['query'] = $parts['query'];
        }
      }
    }

    // Strips all the applicable sections of the URL
    // Note: Scheme and Host are never stripped
    foreach ($keys as $key)
    if ($flags & (int) constant('HTTP_URL_STRIP_' . strtoupper($key))) {
      unset($parse_url[$key]);
    }

    return
      ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
      .((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
      .((isset($parse_url['host'])) ? $parse_url['host'] : '')
      .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
      .((isset($parse_url['path'])) ? $parse_url['path'] : '')
      .((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
      .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
  }

  public static function json_format($json)
  {
    $tab = "  ";
    $new_json = "";
    $indent_level = 0;
    $in_string = false;

    $json_obj = json_decode($json);

    if ($json_obj === false) {
      return false;
    }

    $json = json_encode($json_obj);
    $len = strlen($json);

    for ($c = 0; $c < $len; $c++) {
      $char = $json[$c];
      switch ($char) {
        case '{':
        case '[':
          if (!$in_string) {
            $new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
            $indent_level++;
          }
          else {
            $new_json .= $char;
          }
          break;
        case '}':
        case ']':
          if (!$in_string) {
            $indent_level--;
            $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
          }
          else {
            $new_json .= $char;
          }
          break;
        case ',':
          if (!$in_string) {
            $new_json .= ",\n" . str_repeat($tab, $indent_level);
          }
          else {
            $new_json .= $char;
          }
          break;
        case ':':
          if (!$in_string) {
            $new_json .= ": ";
          }
          else {
            $new_json .= $char;
          }
          break;
        case '"':
          if ($c > 0 && $json[$c - 1] != '\\') {
            $in_string = !$in_string;
          }
        default:
          $new_json .= $char;
          break;
      }
    }

    return $new_json;
  }

  public static function title_case($title)
  {
    // Our array of 'small words' which shouldn't be capitalised if
    // they aren't the first word.  Add your own words to taste.
    $smallwordsarray = array(
      'of','a','the','and','an','or','nor','but','is','if','then','else','when',
      'at','from','by','on','off','for','in','out','over','to','into','with'
    );

    // Split the string into separate words
    $words = explode(' ', $title);
    foreach ($words as $key => $word)
    {
      $word = mb_strtolower($word, 'utf8');

      // If this word is the first, or it's not one of our small words,
      // capitalise it with ucwords()
      if ($key === 0 || !in_array($word, $smallwordsarray)) {
        $word = ucwords($word);
      }

      $words[$key] = $word;
    }

    // Join the words back into a string
    $newtitle = implode(' ', $words);

    return $newtitle;
  }

}
