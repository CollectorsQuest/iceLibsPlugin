<?php

class IceValidatorDateRange extends sfValidatorDateRange
{
  /**
   * @see sfValidatorDateRange
   */
  protected function doClean($value)
  {
    $fromField = $this->getOption('from_field');
    $toField   = $this->getOption('to_field');

    if (!is_array($value))
    {
      @list($from, $to) = explode(' - ', $value);

      $value = array();
      $value[$fromField] = !empty($from) ? date_format(new DateTime($from), 'm/d/Y') : null;
      $value[$toField] = !empty($to) ? date_format(new DateTime($to), 'm/d/Y') : $value[$fromField];
    }

    if ($value[$fromField] && $value[$toField])
    {
      if ($value[$fromField] === $value[$toField] && $value[$toField] !== null)
      {
        $value[$toField] = date('m/d/Y', strtotime('+1 day', strtotime($value[$toField])));
      }

      $v = new sfValidatorSchemaCompare(
        $fromField, sfValidatorSchemaCompare::LESS_THAN_EQUAL, $toField,
        array('throw_global_error' => true), array('invalid' => $this->getMessage('invalid'))
      );
      $v->clean($value);
    }

    return $value;
  }
}
