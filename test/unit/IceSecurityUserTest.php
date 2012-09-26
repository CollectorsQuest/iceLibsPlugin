<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceSecurityUser.class.php';

$t = new lime_test(15, array(new lime_output_color()));
$t->diag('Testing IceSecurityUser.class.php');

$_SERVER['session_id'] = 'test';

$dispatcher = new sfEventDispatcher();
$sessionPath = sys_get_temp_dir().'/sessions_'.rand(11111, 99999);
$storage = new sfSessionTestStorage(array('session_path' => $sessionPath));
$options = array(
    'use_flash' => true,
);
$user = new IceSecurityUser($dispatcher, $storage, $options);


$t->diag('Proper namespaced flash implementation:');

$t->diag('01. Namespaced flash messages do not persist between request when persist param is false');
$user->setFlash('persist_test', 1, $persist = false, $ns = 'extra');

user_flush($dispatcher, $user, $storage, $options);
$t->is($user->hasFlash('persist_test', $ns = 'extra'), null);


$t->diag('02. Namespaced flash messages are properly unset after one request');
$user->setFlash('request_unset_test', 1);
$user->setFlash('request_unset_test', 2, $persist = true, $ns = 'extra');

user_flush($dispatcher, $user, $storage, $options);
$t->is($user->getFlash('request_unset_test'), 1);
$t->is($user->getFlash('request_unset_test', null, $ns = 'extra'), 2);

user_flush($dispatcher, $user, $storage, $options);
$t->is($user->hasFlash('request_unset_test'), false);
$t->is($user->hasFlash('request_unset_test', $ns = 'extra'), false);


$t->diag('03. Test getFlashAndDelete()');
$user->setFlash('get_and_delete_test', 1);
$user->setFlash('get_and_delete_test', 2, $ns = 'extra');

$t->is($user->getFlashAndDelete('get_and_delete_test'), 1);
$t->is($user->hasFlash('get_and_delete_test'), false);
$t->is($user->getFlashAndDelete('get_and_delete_test', null, $ns = 'extra'), 2);
$t->is($user->hasFlash('get_and_delete_test', $ns = 'extra'), false);

$t->diag('04. Make sure symfony\'s flash implementation still works:');
// ->setFlash() ->getFlash() ->hasFlash()
$t->diag('->setFlash() ->getFlash() ->hasFlash()');
$user->initialize($dispatcher, $storage, array('use_flash' => true));
$user->setFlash('foo', 'bar');
$t->is($user->getFlash('foo'), 'bar', '->setFlash() sets a flash variable');
$t->is($user->hasFlash('foo'), true, '->hasFlash() returns true if the flash variable exists');
user_flush($dispatcher, $user, $storage, array('use_flash' => true));

$userBis = new IceSecurityUser($dispatcher, $storage, array('use_flash' => true));
$t->is($userBis->getFlash('foo'), 'bar', '->getFlash() returns a flash previously set');
$t->is($userBis->hasFlash('foo'), true, '->hasFlash() returns true if the flash variable exists');
user_flush($dispatcher, $user, $storage, array('use_flash' => true));

$userBis = new IceSecurityUser($dispatcher, $storage, array('use_flash' => true));
$t->is($userBis->getFlash('foo'), null, 'Flashes are automatically removed after the next request');
$t->is($userBis->hasFlash('foo'), false, '->hasFlash() returns true if the flash variable exists');
/* */

$storage->clear();

function user_flush($dispatcher, $user, $storage, $options = array())
{
  $user->shutdown();
  $user->initialize($dispatcher, $storage, $options);
  $parameters = $storage->getOptions();
  $storage->shutdown();
  $storage->initialize($parameters);
}
