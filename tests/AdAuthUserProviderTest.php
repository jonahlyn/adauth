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

  protected $config;
  protected $entry;
  protected $ldap_mocker;

  public function setUp()
  {
    $this->config = array(
      'debug'     => false,
      'host'      => '',
      'port'      => 389,
      'dn_user'   => '',
      'dn_pass'   => '',
      'groups'    => array(),
      'admins'    => array(),
      'custom'  => array()
    );

    $this->entry = array(
      'dn'              => 'cn=username',
      'samaccountname'  => array(
        'count' => 1,
        0       => 'username'
      ),
      'memberof'        => array()
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

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testShouldSetUserIdEqualToDn() {
    $provider = new AdauthUserProvider($this->config);
    $user = $provider->clean($this->entry);

    $this->assertEquals($user->__get('id'), $this->entry['dn']);
  }

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testShouldSetUsernameToSamaccountname() {
    $provider = new AdauthUserProvider($this->config);
    $user = $provider->clean($this->entry);

    $this->assertEquals($user->__get('username'), $this->entry['samaccountname'][0]);
  }

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testDefaultTypeShouldBeUserWhenNoViewGroupsConfigured() {
    $this->config['groups'] = array();
    $provider = new AdauthUserProvider($this->config);
    $user = $provider->clean($this->entry);
    $this->assertEquals($user->__get('type'), 1);
    $this->assertEquals($user->__get('group'), '');
  }

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testShouldReturnNullWhenUserIsNotMemberOfViewGroup() {
    $this->config['groups'] = array('CN=IT-AllITUsers-GG,OU=Groups,OU=IT,DC=colleges,DC=ad,DC=unm,DC=edu');
    $provider = new AdauthUserProvider($this->config);
    $this->entry['memberof'] = array();
    $user = $provider->clean($this->entry);

    $this->assertEquals($user, NULL);
  }

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testShouldSetTypeAndGroupWhenMemberOfViewGroup() {
    $view_group = '';

    $this->config['groups'] = array($view_group);
    $provider = new AdauthUserProvider($this->config);

    $this->entry['memberof'][0] = $view_group;
    $user = $provider->clean($this->entry);

    $this->assertEquals($user->__get('type'), 1);
    $this->assertEquals($user->__get('group'), $view_group);
  }

  /**
   * @covers AdauthUserProvider::clean
   */
  public function testShouldSetUserTypeAndGroupWhenUserIsAnAdmin() {
    $admin_group = 'cn=admins';

    $this->config['admins'] = array($admin_group);
    $provider = new AdauthUserProvider($this->config);

    $this->entry['memberof'][0] = $admin_group;
    $user = $provider->clean($this->entry);

    $this->assertEquals($user->__get('type'), 0);
    $this->assertEquals($user->__get('group'), $admin_group);
  }


  public function testShouldSetUserTypeAndGroupForCustomGroupsMembers() {
    $custom_group = 'cn=students';

    $this->config['custom'] = array(
      2 => $custom_group
    );
    $provider = new AdauthUserProvider($this->config);

    $this->entry['memberof'][0] = $custom_group;
    $user = $provider->clean($this->entry);

    $this->assertEquals($user->__get('type'), 2);
    $this->assertEquals($user->__get('group'), $custom_group);
  }

}
 