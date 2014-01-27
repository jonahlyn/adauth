<?php
/**
 * Created by PhpStorm.
 * User: jonahlyn
 * Date: 1/27/14
 * Time: 11:14 AM
 */

namespace Unm\Adauth;

use \Mockery as m;

class AdAuthUserProviderTest extends  \Orchestra\Testbench\TestCase {

  public function setUp()
  {
    $this->config = array(
      'debug'   => false,
      'host'    => 'test',
      'port'    => 389,
      'dn_user' => '',
      'dn_pass' => ''
    );

    $this->ldap_mocker = \PHPUnit_Extension_FunctionMocker::start($this, 'Unm\Adauth')
      ->mockFunction('ldap_set_option')
      ->mockFunction('ldap_connect')
      ->mockFunction('ldap_bind')
      ->mockFunction('ldap_unbind')
      ->mockFunction('ldap_error')
      ->getMock();

    $this->ldap_mocker
      ->expects($this->any())
      ->method('ldap_connect')
      ->will($this->returnValue('ldap'));

    $this->ldap_mocker
      ->expects($this->any())
      ->method('ldap_bind')
      ->will($this->returnValue(true));
  }

  public function tearDown()
  {
    \PHPUnit_Extension_FunctionMocker::tearDown();
  }

  public function testShouldEnableDebuggingIfConfigOptionSet()
  {
    $this->ldap_mocker
      ->expects($this->at(0))
      ->method('ldap_set_option')
      ->with(NULL, LDAP_OPT_DEBUG_LEVEL, 7)
      ->will($this->returnValue(true));

    $this->config['debug'] = true;

    $provider = new AdauthUserProvider($this->config);
  }

  public function testShouldSetOptionsRequiredForActiveDirectoryConnection()
  {
    $this->ldap_mocker
      ->expects($this->at(1))
      ->method('ldap_set_option')
      ->with('ldap', LDAP_OPT_PROTOCOL_VERSION, 3)
      ->will($this->returnValue(true));

    $this->ldap_mocker
      ->expects($this->at(2))
      ->method('ldap_set_option')
      ->with('ldap', LDAP_OPT_REFERRALS, 0)
      ->will($this->returnValue(true));

    $provider = new AdauthUserProvider($this->config);
  }


}
 