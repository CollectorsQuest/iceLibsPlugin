<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceTypes.class.php';

$t = new lime_test(8, array(new lime_output_color()));

$t->diag('IceTypeUrl');

  $url = new IceTypeUrl('http://www.google.com');
  $t->is((string) $url, 'http://www.google.com', 'Settig URL');

  $url->addQueryString('q', 'flowers');
  $t->is((string) $url, 'http://www.google.com?q=flowers', 'Adding query string');

  $url->addQueryString('q', 'grass');
  $t->is((string) $url, 'http://www.google.com?q=flowers', 'Adding query string which already exists');

  $url->replaceQueryString('q', 'grass');
  $t->is((string) $url, 'http://www.google.com?q=grass', 'Replacing query string which exists');

  $url->replaceQueryString('s', 'window');
  $t->is((string) $url, 'http://www.google.com?q=grass&s=window', 'Replacing query string which does not exists yet');

  $url->removeQueryString('q');
  $t->is((string) $url, 'http://www.google.com?s=window', 'Removing query string');

  $url->removeQueryString('k');
  $t->is((string) $url, 'http://www.google.com?s=window', 'Removing query string which does not exist');

  $url = new IceTypeUrl('http://www.google.com');
  $url->addQueryString('q', 'help')
      ->addQueryString('s', 'popularity')
      ->addQueryString('o', 'desc');
  $t->is((string) $url, 'http://www.google.com?q=help&s=popularity&o=desc', 'Chaining methods');
