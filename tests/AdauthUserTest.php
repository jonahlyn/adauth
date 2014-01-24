<?php namespace Unm\Adauth;
/**
 * Created by PhpStorm.
 * User: jonahlyn
 * Date: 1/24/14
 * Time: 2:12 PM
 */

use Illuminate\Auth;
use Illuminate\Auth\UserInterface;

class AdauthUserTest extends \Orchestra\Testbench\TestCase {

  /*
   * Output of Auth::user() should be compatible with Response::json
   *
   */
  public function testAuthUserOutputAsJson(){
    $attributes = array(
      'test1' => 'one',
      'test2' => 'two'
    );

    $user = new AdauthUser($attributes);
    $this->be($user);
    $json = json_encode(\Auth::user());

    $this->assertArrayHasKey('attributes', json_decode($json, TRUE));
  }
}
 