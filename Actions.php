<?php namespace components\autologin; if(!defined('TX')) die('No direct access.');

class Actions extends \dependencies\BaseComponent
{
  
  protected function authenticate($data)
  {
    
    tx('Authenticating with autologin link', function()use($data){
      
      //Validate input data.
      $data = $data->having('auth_code', 'failure_url')
        ->auth_code->validate('Authentication code', array('required', 'string', 'not_empty'))->back()
        ->failure_url->validate('Failure url', array('url'))->back()
      ;
      
      $invalid_link = false;
      
      //See if this code is present.
      $link = tx('Sql')
        ->table('autologin', 'AutologinLinks')
        ->where('auth_code', "'{$data->auth_code}'")
        ->execute_single()
        
        //If not, no autologin for you.
        ->is('empty', function()use($data, &$invalid_link){
          $invalid_link = true;
          tx('Logging')->log('Autologin', 'Failed attempt', 'Autologin-link ('.$data->auth_code.') was not found in the database. Attempted from IP '.tx('Data')->server->REMOTE_ADDR);
        })
        
        //Otherwise
        ->failure(function($link)use(&$invalid_link){
          
          //Check expire date.
          $link->expires->lt(time(), function()use($link, &$invalid_link){
            $invalid_link = true;
            tx('Logging')->log('Autologin', 'Failed attempt', 'Autologin-link ('.$link->auth_code.') was expired. Attempted from IP '.tx('Data')->server->REMOTE_ADDR);
          });
          
        });
              
      //Make sure things are ok so far.
      if(!$invalid_link){
        
        //Get the target user information.
        $user = tx('Sql')
          ->table('account', 'Accounts')
          ->pk($link->user_id)
          ->join('UserInfo', $UI)
          ->where("(`$UI.status` & 1)", '>', 0)
          ->execute_single()
          ->is('empty', function()use(&$invalid_link){
            $invalid_link = true;
            tx('Logging')->log('Autologin', 'Failed attempt', 'User ID of the autologin-link ('.$link->user_id.') does not resolve to a valid and active user.');
          });
        
        throw new \Exception('TODO check admin level');
        
      }
      
      //When the link is invalid.
      if($invalid_link){
        
        //Either way, redirect to the failure_url.
        tx('Url')->redirect(url($data->failure_url . '&auth_code=NULL&failure_url=NULL'));
        
        //If the user is already logged in, show different message.
        if(tx('Account')->user->check('login'))
          throw new \exception\Validation('The automatic login link used was invalid or has expired. You were already logged in however, you can proceed with that account.');
        
        //If the user is not logged in, the shorter version.
        else
          throw new \exception\Validation('The automatic login link used was invalid or has expired.');
        
      }
      
      //Ok we are good to go! The link is valid.
      
      //If the user is already logged in, logout first.
      if(tx('Account')->user->check('login'))
        tx('Account')->logout();
      
      //Save the current IP address and current session as the last login.
      $user
        ->ipa->set(tx('Data')->server->REMOTE_ADDR)->back()
        ->session->set(tx('Session')->id)->back()
        ->save();
      
      //Set user in session.
      tx('Data')->session->user->set(array(
        'id' => $user->id->get('int'),
        'email' => $user->email->get('string'),
        'level' => $user->level->get('int'),
        'ipa' => $user->ipa->get('string'),
        'login' => true
      ));
      tx('Account')->user->set(tx('Data')->session->user);
      
      //Redirect to the success_url.
      tx('Url')->redirect(url($link->success_url, true));
      
      tx('Logging')->log('Autologin', 'Performed autologin', 'Autologin-link ('.$link->auth_code.') was used successfully for user ID '.$user->id.' from IP '.tx('Data')->server->REMOTE_ADDR);
      
    })
    
    ->failure(function($info){
      tx('Controller')->message(array(
        'error' => $info->get_user_message()
      ));
    });
    
  }
  
}
