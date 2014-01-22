<?php namespace Unm\Adauth;

use Illuminate\Auth\Guard;

/**
 * Class AdauthGuard
 * @package Unm\Adauth
 */
class AdauthGuard extends Guard
{

  /** Check if user is logged in as admin
   * @return bool
   */
  public function admin() {
    // Check if user is logged in
    if ($this->check() && $this->user()) {
      return $this->user()->type == 0;
    }
    return FALSE;
  }

}