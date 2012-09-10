<?php namespace components\autologin; if(!defined('TX')) die('No direct access.');

class Helpers extends \dependencies\BaseComponent
{
  
  protected
    
    $permissions = array(
      'generate_autologin_link' => 2
    );
  
  /**
   * Generates an autologin-link for one user.
   *
   * @author Beanow
   * @param int $data->user_id The user ID to generate an autologin-link for.
   * @param datetime $data->expires The expire date for the link to be generated.
   * @param Url $data->success_url The url to redirect to when a valid link has been provided.
   * @param Url $data->failure_url The url to redirect to when the link is not valid.
   * @return Url The autologin-link that was generated.
   */
  protected function generate_autologin_link($data)
  {
    
    //Validate data.
    $data = $data->having('user_id', 'expires', 'success_url', 'failure_url')
      ->user_id->validate('User ID', array('required', 'number'=>'integer', 'gt'=>0))->back()
      ->success_url->validate('Success URL', array('required', 'url'))->back()
      ->failure_url->validate('Failure URL', array('required', 'url'))->back()
      ->expires->validate('Expires', array('datetime'))->back()
    ;
    
    //If no expire date is given, take the standard of 5 days.
    $data->expires->not('set', function()use($data){
      $data->merge(array(
        'expires' => time() + (5 * 24 * 3600)
      ));
    });
    
    //Check user exists.
    $user = tx('Sql')
      ->table('account', 'Accounts')
      ->pk($data->user_id)
      ->execute_single()
      
      //User not found.
      ->not('set', function(){
        throw new \exception\InvalidArgument('User with this ID does not exist.');
      })
      
      //Admins should never have autologin links. They're high impact targets.
      ->is_administrator
        ->is('true', function(){
          throw new \exception\InvalidArgument('User has administrator rights and therefore must not use autologin-links.');
        })
      ->back();
    
    $auth_code = Data();
    
    do
    {
      
      //Generate auth_code.
      $auth_code->set(tx('Security')->random_string());
      
      //Check for duplicates.
      $duplicates = tx('Sql')
        ->table('autologin', 'AutologinLinks')
        ->where('auth_code', "'{$auth_code}'")
        ->count();
      
      //If unique, exit the loop.
      if($duplicates->get() == 0)
        break;
      
    }
    while(true)
    
    //Generate autologin-link model and save it.
    $link = tx('Sql')
      ->model('autologin', 'AutologinLinks')
      ->set(array(
        'user_id' => $data->user_id,
        'expires' => $data->expires,
        'auth_code' => $auth_code,
        'success_url' => $data->success_url
      ))
      ->save();
    
    //Return the Url for this link.
    return url("?action=autologin/authenticate&auth_code={$link->auth_code}&email={$user->email}&failure_url=".urlencode($data->failure_url->get()), true);
    
  }
  
}
