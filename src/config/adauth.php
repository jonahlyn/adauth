<?php

/**
 * Active Directory Configuration
 */
return array(

  // Debug setting
  'debug' => true,

  // Domain controllers (ldap://host1 ldap://host2 ...)
  'host' => '',

  // Port (389 or 636)
  'port' => 389,

  // OU containing users (basedn)
  'basedn' => '',

  // User Domain
  'domain' => '',

  // Domain credentials the app should use to access DC
  // (This user doesn't need any privileges)
  'dn_user' => '',
  'dn_pass' => '',

  // Attributes to return
  'attributes' => array(
    'dn',         // required
    'cn',         // required
    'memberof',   // required
  ),

  // Configure access via groups
  // ACL: 0 = admin, 1 = user, 2 = special

  // Groups with view access (Optional)
  // array() == Default to all users.
  'groups' => array(),

  // Groups with admin access (Optional)
  'admins' => array(),

  // Custom Groups (Optional)
  'custom' => array(
    // 2 => 'some_group'
  ),

);