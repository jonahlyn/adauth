<?php namespace Unm\Adauth;

use Illuminate\Auth\UserProviderInterface;
use Illuminate\Auth\UserInterface;

/**
 * Class AdauthUserProvider
 * @package Unm\Adauth
 */
class AdauthUserProvider implements UserProviderInterface
{
  /**
   * The user model
   * @var AdauthUser
   */
  protected $model;

  /**
   * Create a new AdauthUserProvider
   *
   * @param  array $config
   * @return void
   */
  public function __construct($config)
  {
    $this->config = $config;

    // Set DEBUGGING
    if ($this->config['debug']) {
      ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
    }

    // Connect to the domain controller
    if (!$this->conn = ldap_connect($this->config['host'], $this->config['port'])) {
      throw new \Exception("Could not connect to AD host {$this->config['host']}: " . ldap_error($this->conn));
    }

    // Required for Windows AD
    ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);

    // Bind to AD
    if (!@ldap_bind($this->conn, "{$this->config['dn_user']}@{$this->config['domain']}", $this->config['dn_pass']))
    {
      throw new \Exception('Could not bind to AD: ' . "{$this->config['dn_user']}: " . ldap_error($this->conn));
    }
  }

  /**
   * Destroy AdauthUserProvider
   */
  public function __destruct()
  {
    if (!is_null($this->conn)) {
      ldap_unbind($this->conn);
    }
  }

  /**
   * Retrieve a user by their unique identifier (DN)
   *
   * @param  mixed $identifier
   * @return AdauthUser|null
   */
  public function retrieveByID($identifier)
  {
    $result = @ldap_read($this->conn, $identifier, 'objectclass=*', $this->config['attributes']);
    if ($result === FALSE) {
      return null;
    }

    $entries = ldap_get_entries($this->conn, $result);

    if ($entries['count'] == 0 || $entries['count'] > 1) {
      return null;
    }

    return $this->clean($entries[0]);
  }

  /**
   * Retrieve a user by the given credentials
   *
   * @param  array $credentials
   * @return AdauthUser|null
   */
  public function retrieveByCredentials(array $credentials)
  {
    $result = ldap_search($this->conn, $this->config['basedn'], "samaccountname=" . $credentials['username'], $this->config['attributes']);
    if ($result === FALSE)
    {
      return NULL;
    }

    $entries = ldap_get_entries($this->conn, $result);

    if ($entries['count'] == 0 || $entries['count'] > 1) {
      return NULL;
    }

    return $this->clean($entries[0]);
  }

  /**
   * Validate a user against the given credentials
   *
   * @param  AdauthUser $user
   * @param  array $credentials
   * @return bool
   */
  public function validateCredentials(UserInterface $user, array $credentials)
  {
    if ($user == NULL) {
      return FALSE;
    }

    if ($credentials['password'] == '') {
      return FALSE;
    }

    if (!$result = @ldap_bind($this->conn, $user->id, $credentials['password']))
    {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks Access and Creates the User Model
   * @param  array $entry
   * @return AdauthUser|null
   */
  public function clean(array $entry)
  {
    $entry['id'] = $entry['dn'];
    $entry['username'] = $entry['samaccountname'][0];

    // Set default user type (ACL: 0 = admin, 1 = user)
    $entry['type'] = 1;
    $entry['group'] = '';

    $groups = array_map('strtolower', array_values($entry['memberof']));

    // View Group Check
    if (count($this->config['groups']) > 0) {
      $entry['type'] = NULL;
    }

    foreach ($this->config['groups'] as $group) {
      if (isset($entry['dn']) && in_array(strtolower($group), $groups)) {
        $entry['type'] = 1;
        $entry['group'] = $group;
      }
    }

    // Admin Group Check
    foreach ($this->config['admins'] as $group) {
      if (isset($entry['dn']) && in_array(strtolower($group), $groups)) {
        $entry['type'] = 0;
        $entry['group'] = $group;
      }
    }

    // Override Check
    foreach ($this->config['override'] as $username => $access)
    {
      if (isset($entry['samaccountname']) && $entry['samaccountname'][0] == $username)
      {
        $entry['type'] = $access;
        $entry['group'] = 'custom';
      }
    }

    // User does not have access
    if ($entry['type'] === NULL) {
      return NULL;
    }

    $this->model = new AdauthUser($entry);
    return $this->model;
  }

}