<?php

/** @var $breadcrumbs array */
foreach($breadcrumbs as $key => $breadcrumb)
{
  if ($breadcrumb['url'] != null)
  {
    echo link_to($breadcrumb['name'], $breadcrumb['url']);
  }
  else
  {
    echo $breadcrumb['name'];
  }

  if ($key < count($breadcrumbs) - 1)
  {
    /** @var $separator string */
    echo $separator;
  }
}
