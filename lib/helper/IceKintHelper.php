<?php

include_once __DIR__ .'/../vendor/Kint/Kint.class.php';
Kint::_init();

/**
 * Alias of {@link Kint::dump()}
 *
 * @param mixed $data,...
 *
 * @see Kint::dump()
 */
if (!function_exists('d'))
{
  function d()
  {
    if (!Kint::enabled()) return null;

    $args = func_get_args();
    return call_user_func_array(array('Kint', 'dump'), $args);
  }

  function dd()
  {
    if (!Kint::enabled()) return;

    $args = func_get_args();
    call_user_func_array(array('Kint', 'dump'), $args);
    die;
  }
}

if (!function_exists('s'))
{
  function s()
  {
    if (!Kint::enabled()) return;

    $argv = func_get_args();
    echo '<pre>';
    foreach ($argv as $k => $v) {
      $k && print("\n\n");
      echo KintLite($v);
    }
    echo '</pre>';
  }

  function sd()
  {
    if (!Kint::enabled()) return;

    echo '<pre>';
    foreach (func_get_args() as $k => $v) {
      $k && print("\n\n");
      echo KintLite($v);
    }
    echo '</pre>';
    die;

  }

  /**
   * sadly not DRY yet
   *
   * @param $var
   * @param int $level
   * @return string
   */
  function KintLite(&$var, $level = 0)
  {
    // initialize function names into variables for prettier string output (html and implode are also DRY)
    $html = "htmlspecialchars";
    $implode = "implode";
    $strlen = "strlen";
    $count = "count";
    $getClass = "get_class";


    if ($var === NULL) {
      return 'NULL';
    }
    elseif (is_bool($var))
    {
      return 'bool ' . ($var ? 'TRUE' : 'FALSE');
    }
    elseif (is_bool($var))
    {
      return 'bool ' . ($var ? 'TRUE' : 'FALSE');
    }
    elseif (is_float($var))
    {
      return 'float ' . $var;
    }
    elseif (is_int($var))
    {
      return 'integer ' . $var;
    }
    elseif (is_resource($var))
    {
      /** @var $var resource */
      if (($type = get_resource_type($var)) === 'stream' AND $meta = stream_get_meta_data($var))
      {
        if (isset($meta['uri'])) {
          $file = $meta['uri'];

          return "resource ({$type}) {$html($file,0)}";
        }
        else
        {
          return "resource ({$type})";
        }
      }
      else
      {
        return "resource ({$type})";
      }
    }
    elseif (is_string($var))
    {
      return "string ({$strlen($var)}) \"{$html($var)}\"";
    }
    elseif (is_array($var))
    {
      $output = array();
      $space = str_repeat($s = '    ', $level);

      static $marker;

      if ($marker === NULL) {
        // Make a unique marker
        $marker = uniqid("\x00");
      }

      if (empty($var)) {
        return "array (0)";
      }
      elseif (isset($var[$marker]))
      {
        $output[] = "(\n$space$s*RECURSION*\n$space)";
      }
      elseif ($level < 5)
      {
        $isSeq = array_keys($var) === range(0, count($var) - 1);

        $output[] = "(";

        $var[$marker] = TRUE;


        foreach ($var as $key => &$val)
        {
          if ($key === $marker) continue;

          $key = $space . $s . ($isSeq ? "" : "'{$html($key,0)}' =>");

          $dump = KintLite($val, $level + 1);
          $output[] = "{$key} {$dump}";
        }

        unset($var[$marker]);
        $output[] = "$space)";

      }
      else
      {
        $output[] = "(\n$space$s*depth too great*\n$space)";
      }
      return "array ({$count($var)}) {$implode("\n", $output)}";
    }
    elseif (is_object($var))
    {
      // Copy the object as an array
      $array = (array)$var;

      $output = array();
      $space = str_repeat($s = '    ', $level);

      $hash = spl_object_hash($var);

      // Objects that are being dumped
      static $objects = array();

      if (empty($array)) {
        return "object {$getClass($var)} {0}";
      }
      elseif (isset($objects[$hash]))
      {
        $output[] = "{\n$space$s*RECURSION*\n$space}";
      }
      elseif ($level < 5)
      {
        $output[] = "{";
        $objects[$hash] = TRUE;

        $reflection = new ReflectionClass($var);
        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property)
        {
          if ($property->isPrivate()) {
            $property->setAccessible(true);
            $access = "private";
          } elseif ($property->isProtected()) {
            $property->setAccessible(true);
            $access = "protected";
          } else {
            $access = 'public';
          }
          $access = $access . " static";
          $key = $property->getName();

          $value = $property->getValue();
          $output[] = "$space$s{$access} {$key} :: " . KintLite($value, $level + 1);
        }

        foreach ($array as $key => & $val)
        {
          if ($key[0] === "\x00") {

            $access = $key[1] === "*" ? "protected" : "private";

            // Remove the access level from the variable name
            $key = substr($key, strrpos($key, "\x00") + 1);
          }
          else
          {
            $access = "public";
          }

          $output[] = "$space$s$access $key -> " . KintLite($val, $level + 1);
        }
        unset($objects[$hash]);
        $output[] = "$space}";

      }
      else
      {
        $output[] = "{\n$space$s*depth too great*\n$space}";
      }

      return "object {$getClass($var)} ({$count($array)}) {$implode("\n", $output)}";
    }
    else
    {
      return gettype($var) . htmlspecialchars(var_export($var, true), ENT_NOQUOTES);
    }
  }
}
