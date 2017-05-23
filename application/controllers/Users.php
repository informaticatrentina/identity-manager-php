<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

require APPPATH . '/third_party/vendor/autoload.php';


/**
 * Api
 * 
 * @package Aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2017
 * @link  http://www.jlbbooks.it
 * @since Version 1.1.0
 */
class Users extends REST_Controller 
{
  function __construct()
  {
    parent::__construct();
    $this->load->helper('security');
  }
  
  public function index_get($user_id=null)
  {    
    $params=$this->get();

    //file_put_contents('debug.log',print_r($params,TRUE),FILE_APPEND);

    // Verifica password
    if(!empty($params) && $user_id==NULL)
    {     
     
      if(isset($params['where']) && !empty($params['where']))
      {
        $where_string = json_decode($params['where'],TRUE);

         file_put_contents('debug.log','DEBUG1',FILE_APPEND);       
        // Il Profile Manager gestische il tutto con array Json mentre le istanze tramite stringa GET - Damn!
        // é un array json 
        if(json_last_error() == JSON_ERROR_NONE)
        {
          file_put_contents('debug.log','DEBUG2',FILE_APPEND);       
          if(isset($where_string['$or']))
          {
            if(!empty($where_string['$or']))
            {               
              if(isset($where_string['$or'][0]) && count($where_string['$or'][0]==1))
              {
                
                if(isset($where_string['$or'][0]['email']) && !empty($where_string['$or'][0]['email']))
                {
                  return $this->_checkEmail($where_string['$or'][0]['email']);
                }
                else
                {
                  if (is_array($where_string['$or']) && count($where_string['$or']) > 0)
		              {
                    $where_conditions=array();
                    foreach($where_string['$or'] as $val)
                    {
                      if(isset($val['_id'])) $where_conditions[]=new MongoId($val['_id']);                      
                    }

                    $data=$this->mongo_db->where_in('_id', $where_conditions)->get('users');            
                    if(!empty($data))
                    {
                      foreach($data as $key => $value)
                      {   
                        if(isset($value['_created']))
                        {                                                               
                          date_default_timezone_set('Europe/Rome');                        
                          $data[$key]['_created']=date('Y-m-d H:i:s',$value['_created']->sec);
                        } 
                        if(isset($value['_updated']))
                        {                                                               
                          date_default_timezone_set('Europe/Rome');                        
                          $data[$key]['_updated']=date('Y-m-d H:i:s',$value['_updated']->sec);  
                        } 
                        if(isset($value['_id']))
                        {
                          $data[$key]['_id']=(string)$value['_id'];
                        }
                          $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => 'www.google.it'));
                        // Elimino Password e ResetPwd
                        unset($data[$key]['password']);
                        unset($data[$key]['resetpwd']);
                      }
                    }                   
                    $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
                    return;                	             
		              }              
                }
              }
              
            }
          }

          if(!empty($where_string) && isset($where_string['email']) && isset($where_string['password']) && !empty($where_string['email']) && !empty($where_string['password']))
          {
            $email=urldecode($where_string['email']);
            $password=urldecode($where_string['password']);
          
            $data=$this->mongo_db->where(array('email' => $email))->get('users');
   
            if(empty($data))
            {
              $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
              return;
            }
            else
            {              
              // Verifico la password
              if(isset($data[0]['password']) && !empty($data[0]['password']))
              {
                $context = new PHPassLib\Application\Context;
                $context->addConfig('bcrypt', array ('rounds' => 8));
                
                if($context->verify($password, $data[0]['password'])) 
                {
                  // Converto il campo _id in string
                  $data[0]['_id']=(string)$data[0]['_id'];
                  // Non trasmetto la password
                  unset($data[0]['password']);              
                  $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
                  return;
                }
                else
                {                  
                  // Hack SB PASSWORD                                    
                  // Se la password è già stata resettata -> ERRORE
                  if(isset($data[0]['resetpwd']) && $data[0]['resetpwd']==1) 
                  {
                    $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
                    return;
                  }
                  else
                  {
                    // Resetto la password
                    $context = new PHPassLib\Application\Context;
                    $context->addConfig('bcrypt', array ('rounds' => 8)); 
                    // Hash a password                
                    $passwordhash=$context->hash($password);                    
                    $this->mongo_db->where(array('_id' => new MongoId((string)$data[0]['_id'])))->set(array('password' => $passwordhash, 'resetpwd' => 1))->update('users');    
                    
                    // Converto il campo _id in string
                    $data[0]['_id']=(string)$data[0]['_id'];
                    // Non trasmetto la password
                    unset($data[0]['password']);              
                    $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
                    return;
                  }                  
                }
              }
              else  
              {
                $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
                return;
              }
            }
          }    
          
                  // Verifico Username
          if(!empty($where_string) && isset($where_string['email']) && !empty($where_string['email']))
          {
            $email=urldecode($where_string['email']);   
  
            $data=$this->mongo_db->where(array('email' => $email))->get('users');
          
            if(empty($data))
            {
             $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
             return;
            }
            else
            {            
              if(isset($data[0]['password'])) unset($data[0]['password']);
              if(isset($data[0]['_id'])) $data[0]['_id']=(string)$data[0]['_id'];            
              $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
              return;
            }
          }
          
          // Verifico _id
          if(!empty($where_string) && isset($where_string['_id']) && !empty($where_string['_id']))
          {
            try
            {
              $mongo_user_id = new MongoId($where_string['_id']);
            }
            catch (MongoException $ex)
            {
              $this->response(array('response' => 'ERR', 'message' => 'Utente ID non valido.'), REST_Controller::HTTP_OK);
              return;
            }

            $data=$this->mongo_db->where(array('_id' => $mongo_user_id))->get('users');


            if(empty($data))
            {
              $this->response(array('response' => 'ERR', '_items' => $mongo_user_id), REST_Controller::HTTP_OK);
              return;
            }
            else
            {
              // Converto il campo _id in string
              $data[0]['_id']=(string)$data[0]['_id'];
              // Non trasmetto la password
              unset($data[0]['password']);
              $response_arr=$data[0];
              $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
              return;
            }
          }        
        
          // Verifico Nickname
          if(!empty($where_string) && isset($where_string['nickname']) && !empty($where_string['nickname']))
          {
            $nickname=urldecode($where_string['nickname']);
           
            $data=$this->mongo_db->where(array('nickname' => $nickname))->get('users');
          
            if(empty($data))
            {
              $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
              return;
            }
            else
            {                
              // Converto il campo _id in string            
              $data[0]['_id']=(string)$data[0]['_id'];
              $this->response(array('_items' => $data[0]['_id']), REST_Controller::HTTP_OK); 
              return;
            }          
          }
          $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
          return;         
        }
        else
        {         
          file_put_contents('debug.log','DEBUG3',FILE_APPEND);       
          // Remove Refuso modulo Python char \x22
          if(is_string($params['where']))
          {
            file_put_contents('debug.log','DEBUG4',FILE_APPEND);       
            $params['where'] = str_replace('\x22', '', $params['where']);
          }
          
          $data=explode(",", $params['where']); 

          file_put_contents('debug.log','DEBUG5',FILE_APPEND);  
          file_put_contents('debug.log',print_r($data,TRUE),FILE_APPEND);  
          if(count($data)==2 && isset($data[0]) && isset($data[1]) && !empty($data[0]) && !empty($data[1]))
          {
            $data[0]=str_replace('{', '', $data[0]);
            $data[0]=str_replace('}', '', $data[0]);
            $data[1]=str_replace('{', '', $data[1]);
            $data[1]=str_replace('}', '', $data[1]);
            
            $credentials_email=  explode(":", $data[0]);
            $credentials_password= explode(":", $data[1]);
            
            if(isset($credentials_email[0]) && $credentials_email[0]=='email' && isset($credentials_email[1]) && !empty($credentials_email[1])
               && isset($credentials_password[0]) && $credentials_password[0]=='password' && isset($credentials_password[1]) && !empty($credentials_password[1]))
            {
              $email=urldecode($credentials_email[1]);
              $password=urldecode($credentials_password[1]);      
              
              $data=$this->mongo_db->where(array('email' => $email))->get('users');          
              
              if(empty($data))
              {
                $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
              }
              else
              {                
                // Verifico la password               
                if(isset($data[0]['password']) && !empty($data[0]['password']))
                {                  
                  $context = new PHPassLib\Application\Context;
                  $context->addConfig('bcrypt', array ('rounds' => 8));

                  if($context->verify($password, $data[0]['password'])) 
                  {
                    // Converto il campo _id in string
                    $data[0]['_id']=(string)$data[0]['_id'];
                    // Non trasmetto la password
                    unset($data[0]['password']);              
                    $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
                    return;
                  }
                  else
                  {                   
                    // Hack SB PASSWORD                                    
                    // Se la password è già stata resettata -> ERRORE
                    
                    if(isset($data[0]['resetpwd']) && $data[0]['resetpwd']==1) 
                    {
                      $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
                      return;
                    }
                    else
                    {
                      // Resetto la password
                      $context = new PHPassLib\Application\Context;
                      $context->addConfig('bcrypt', array ('rounds' => 8)); 
                      // Hash a password                
                      $passwordhash=$context->hash($password);                    
                      $this->mongo_db->where(array('_id' => new MongoId((string)$data[0]['_id'])))->set(array('password' => $passwordhash, 'resetpwd' => 1))->update('users');    
                    
                      // Converto il campo _id in string
                      $data[0]['_id']=(string)$data[0]['_id'];
                      // Non trasmetto la password
                      unset($data[0]['password']);              
                      $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
                      return;
                    } 
                  }                  
                }
                else
                {
                  $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
                  return;
                }
              }
            }
            else
            {
              $this->response(array('response' => 'ERR', 'message' => 'Credenziali di accesso non corrette.'), REST_Controller::HTTP_OK);          
              return;
            }
          }
           file_put_contents('debug.log','DEBUG6',FILE_APPEND);  
          if(count($data)==1 && isset($data[0]) && !empty($data[0]))
          {
             file_put_contents('debug.log','DEBUG7',FILE_APPEND);  
            $data[0]=str_replace('{', '', $data[0]);
            $data[0]=str_replace('}', '', $data[0]);
            $data[0]=str_replace('[', '', $data[0]);
            $data[0]=str_replace(']', '', $data[0]);
            $data[0]=str_replace('$or:', '', $data[0]);
            
            $credentials_email=  explode(":", $data[0]);
            
            if(isset($credentials_email[0]) && $credentials_email[0]=='email' && isset($credentials_email[1]) && !empty($credentials_email[1]))
            {
              // Hack  Io Sostengo - Elimino il SOURCE DAL LOGIN email$$SOURCE
              //$email = substr($email, 0, strpos($email, "$$"));
              $email=urldecode($credentials_email[1]);                 
              return $this->_checkEmail($email);             
            }
            else
            {
              $this->response(array('response' => 'ERR', 'message' => 'Credenziali di accesso non corrette.'), REST_Controller::HTTP_OK);
              return;
            }              
          }

          if(count($data))
          file_put_contents('debug.log','DEBUG8',FILE_APPEND); 
          if(empty($count))
          {
             file_put_contents('debug.log','DEBUG9',FILE_APPEND); 
            $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK); 
            return;
          }
        }
      }
      elseif(isset($params['_id']) && !empty($params['_id']))
      {
         file_put_contents('debug.log','DEBUG1 qyuuu',FILE_APPEND);    
        $user_id=$params['_id'];   
  
        $data=$this->mongo_db->where(array('_id' => $user_id))->get('users');
          
        if(empty($data))
        {
          $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
          return;
        }
        else
        {            
          if(isset($data[0]['password'])) unset($data[0]['password']);
          if(isset($data[0]['_id'])) $data[0]['_id']=(string)$data[0]['_id'];            
          $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
          return;
        }
      }
      else
      {
        file_put_contents('debug.log','DEBUG1 qyuuussssssss',FILE_APPEND);    
        $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
      } 
    }
    else // Ottengo i dati dell'utente
    {
      $this->response(array('response' => 'DEBUG', '_items' => 'SONO QUI'), REST_Controller::HTTP_OK);
      return;
      try 
      {
        $mongo_user_id = new MongoId($user_id);
      } 
      catch (MongoException $ex) 
      {
        $this->response(array('response' => 'ERR', 'message' => 'Utente ID non valido.'), REST_Controller::HTTP_OK);
        return;
      }     
   
      $data=$this->mongo_db->where(array('_id' => $mongo_user_id))->get('users');
      if(empty($data))
      {
        $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
        return;
      }
      else
      {
        // Converto il campo _id in string
        $data[0]['_id']=(string)$data[0]['_id'];
        // Non trasmetto la password
        unset($data[0]['password']);
        $response_arr=$data[0];
        $this->response($response_arr, REST_Controller::HTTP_OK);
      }     
    }    
  }
 
  public function index_patch($user_id=null)
  {
    if(empty($user_id)) $this->response(array('response' => 'ERR', 'message' => 'Utente non valido.'), REST_Controller::HTTP_OK);
    
    $patch_data=$this->patch();    
    
    if(isset($patch_data['age']) && !empty($patch_data['age'])) $data['age']=$patch_data['age'];
    if(isset($patch_data['age-range']) && !empty($patch_data['age-range'])) $data['age-range']=$patch_data['age-range'];
    if(isset($patch_data['work']) && !empty($patch_data['work'])) $data['work']=$patch_data['work'];
    if(isset($patch_data['public-authority']) && !empty($patch_data['public-authority'])) $data['public-authority']=$patch_data['public-authority'];
    if(isset($patch_data['last-login']) && !empty($patch_data['last-login'])) $data['last-login']=$patch_data['last-login'];    
    if(isset($patch_data['citizenship']) && !empty($patch_data['citizenship'])) $data['citizenship']=$patch_data['citizenship'];
    if(isset($patch_data['education-level']) && !empty($patch_data['education-level'])) $data['education-level']=$patch_data['education-level'];
    if(isset($patch_data['email']) && !empty($patch_data['email'])) $data['email']=$patch_data['email'];
    if(isset($patch_data['firstname']) && !empty($patch_data['firstname'])) $data['firstname']=$patch_data['firstname'];
    if(isset($patch_data['lastname']) && !empty($patch_data['lastname'])) $data['lastname']=$patch_data['lastname'];
    if(isset($patch_data['nickname']) && !empty($patch_data['nickname'])) $data['nickname']=$patch_data['nickname'];
    if(isset($patch_data['sex']) && !empty($patch_data['sex'])) $data['sex']=$this->patch('sex');
    if(isset($patch_data['location']) && !empty($patch_data['location'])) $data['location']=$patch_data['location'];
    if(isset($patch_data['website']) && !empty($patch_data['website'])) $data['website']=$patch_data['website'];
    if(isset($patch_data['status']) && !empty($patch_data['status'])) $data['status']=$patch_data['status'];    
    if(isset($patch_data['biography']) && !empty($patch_data['biography'])) $data['biography']=$patch_data['biography'];    
    if(isset($patch_data['mobile']) && !empty($patch_data['mobile'])) $data['mobile']=$patch_data['mobile'];
    if(isset($patch_data['site-last-login']) && !empty($patch_data['site-last-login'])) $data['site-last-login']=$patch_data['site-last-login']; 
    if(isset($patch_data['site-user-info']) && !empty($patch_data['site-user-info'])) $data['site-user-info']=$patch_data['site-user-info']; 
    
    
    if(isset($patch_data['profile-info']) && !empty($patch_data['profile-info'])) $data['profile-info']=$patch_data['profile-info'];

    if(isset($patch_data['password']) && !empty($patch_data['password']))
    {
      $context = new PHPassLib\Application\Context;
      $context->addConfig('bcrypt', array ('rounds' => 8)); 
      // Hash a password                
      $data['password']=$context->hash($patch_data['password']);
    }  
     
    
    if(!empty($data))
    {
      date_default_timezone_set("Europe/Rome"); 
      $data['_updated']= new MongoDate();  
      $this->mongo_db->where(array('_id' => new MongoId($user_id)))->set($data)->update('users');    
      $this->response(array('_status' => 'OK'), REST_Controller::HTTP_OK);    
    }
    else $this->response(array('_status' => 'OK', 'msg' => 'no update'), REST_Controller::HTTP_OK);   
  }
  
  public function index_post()
  {
    $post_data=$this->post();
    $error_status='';
    $error_issues=array();
    
    // Verifico Email se esiste    
    
    if(isset($post_data['email']) && !empty($post_data['email']))
    {
      $email=xss_clean($post_data['email']);
      
      $data=$this->mongo_db->where(array('email' => $email))->get('users');     
          
      if(!empty($data))
      {
        $error_status='ERR';
        $error_issues=array('email' => 'is not unique');
      }      
    }
    else $this->response(array('_status' => 'ERR', 'message' => 'Il campo email è obbligatorio.'), REST_Controller::HTTP_OK);
    
    // Verifico Nickname
    if(isset($post_data['nickname']) && !empty($post_data['nickname']))
    {
      $nickname=xss_clean($post_data['nickname']);
           
      $data=$this->mongo_db->where(array('nickname' => $nickname))->get('users');
          
      if(!empty($data))
      {
        $error_status='ERR';
        $error_issues=array('nickname' => 'is not unique');
      }
    }    
    
    if(!empty($error_status))
    {
      $this->response(array('_status' => $error_status, '_issues' => $error_issues), REST_Controller::HTTP_OK);        
    }
    else
    {
      if(isset($post_data['email']) && !empty($post_data['email'])) $data['email']=trim(xss_clean($post_data['email']));
      if(isset($post_data['type']) && !empty($post_data['type'])) $data['type']=$post_data['type'];
      if(isset($post_data['source']) && !empty($post_data['source'])) $data['source']=$post_data['source'];
      if(isset($post_data['status'])) $data['status']=(string) $post_data['status'];
      if(isset($post_data['mobile']) && !empty($post_data['mobile'])) $data['mobile']=$post_data['mobile'];
      if(isset($post_data['nickname']) && !empty($post_data['nickname'])) $data['nickname']=trim(xss_clean($post_data['nickname']));
    
      if(isset($post_data['profile-info']) && !empty($post_data['profile-info'])) $data['profile-info']=$post_data['profile-info'];
      
      if($post_data['type']=='org')
      {
        if(isset($post_data['firstname']) && !empty($post_data['firstname'])) $data['firstname']=trim(xss_clean($post_data['firstname']));
        if(isset($post_data['lastname']) && !empty($post_data['lastname'])) $data['lastname']='';
      }
      else
      {
        if(isset($post_data['firstname']) && !empty($post_data['firstname'])) $data['firstname']=trim(xss_clean($post_data['firstname']));
        if(isset($post_data['lastname']) && !empty($post_data['lastname'])) $data['lastname']=trim(xss_clean($post_data['lastname']));
      }
      
      date_default_timezone_set("Europe/Rome"); 
      $time=new MongoDate();
      $data['_updated']=$time;
      $data['_created']=$time;
      
      $data['resetpwd'] = 1;
      
      if(isset($post_data['password']) && !empty($post_data['password']))
      {
        $context = new PHPassLib\Application\Context;
        $context->addConfig('bcrypt', array ('rounds' => 8)); 
        // Hash a password                
        $data['password']=$context->hash($post_data['password']);
      }      
      
      $this->mongo_db->insert('users', $data);
      
      $data=$this->mongo_db->select(array('_id'))->where(array('email' => trim(xss_clean($post_data['email']))))->get('users');     
      
      if(isset($data[0]['_id']) && !empty($data[0]['_id']))
      {
        $this->response(array('_status' => 'OK', '_id' => (string)$data[0]['_id']), REST_Controller::HTTP_OK);        
      }
      else
      {
        $this->response(array('_status' => 'ERR', '_issues' => 'Errore durante la creazione dell\'utente'), REST_Controller::HTTP_OK);
      }      
    }
  }
  
  private function _checkEmail($email)
  {
    if(empty($email)) $this->response(array('response' => 'ERR', 'message' => 'Email non valida'), REST_Controller::HTTP_OK);
    
    $data=$this->mongo_db->where(array('email' => $email))->get('users');
          
    if(empty($data))
    {
      return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);      
    }
    else
    {            
      if(isset($data[0]['password'])) unset($data[0]['password']);
      if(isset($data[0]['_id'])) $data[0]['_id']=(string)$data[0]['_id'];            
      return $this->response(array('_items' => $data), REST_Controller::HTTP_OK);      
    }
  }
}
