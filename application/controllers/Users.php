<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;


/**
 * Api
 * 
 * @package Aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2017
 * @link  http://www.jlbbooks.it
 * @since Version 1.0.0
 */
class Users extends REST_Controller 
{
  function __construct()
  {
    parent::__construct();   
  }
  
  public function index_get($user_id=null)
  {    
    $params=$this->get();   
        
    // Verifica password
    if(!empty($params) && $user_id==NULL)
    {      
      if(isset($params['where']) && !empty($params['where']))
      {
        $where_string = json_decode($params['where'],TRUE);
        //die(print('<pre>'.print_r($where_string,TRUE).'</pre>')); 
        
        // Verifico Username e password
        if(!empty($where_string) && isset($where_string['email']) && isset($where_string['password']) && !empty($where_string['email']) && !empty($where_string['password']))
        {
          $email=urldecode($where_string['email']);
          $password=urldecode($where_string['password']);
          
          $data=$this->mongo_db->where(array('email' => $email))->get('users');
          
          if(empty($data))
          {
            $this->response(array('response' => 'ERR', 'message' => 'Utente non valido.'), REST_Controller::HTTP_OK);
          }
          else
          {    
            // Verifico la password
            if(isset($data[0]['password']) && !empty($data[0]['password']) && password_verify($password,$data[0]['password']))
            {              
              // Converto il campo _id in string
              $data[0]['_id']=(string)$data[0]['_id'];
              // Non trasmetto la password
              unset($data[0]['password']);              
              $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
            }
            else  $this->response(array('response' => 'ERR', 'message' => 'Credenziali di accesso non corrette.'), REST_Controller::HTTP_OK);
          }
        }
        
        // Verifico Username
        if(!empty($where_string) && isset($where_string['email']) && !empty($where_string['email']))
        {
          $email=urldecode($where_string['email']);   
  
          $data=$this->mongo_db->where(array('email' => $email))->get('users');
          
          if(empty($data))
          {
            $this->response(array('response' => 'ERR', 'message' => 'Utente non valido.'), REST_Controller::HTTP_OK);
          }
          else
          {            
            if(isset($data[0]['password'])) unset($data[0]['password']);
            if(isset($data[0]['_id'])) $data[0]['_id']=(string)$data[0]['_id'];            
            $this->response(array('_items' => $data), REST_Controller::HTTP_OK);
          }
        }      
        
        
        // Verifico Nickname
        if(!empty($where_string) && isset($where_string['nickname']) && !empty($where_string['nickname']))
        {
          $nickname=urldecode($where_string['nickname']);
           
          $data=$this->mongo_db->where(array('nickname' => $nickname))->get('users');
          
          if(empty($data))
          {
            $this->response(array('response' => 'ERR', 'message' => 'Utente non valido.'), REST_Controller::HTTP_OK);
          }
          else
          {                
            // Converto il campo _id in string            
            $data[0]['_id']=(string)$data[0]['_id'];
            $this->response(array('_items' => $data[0]['_id']), REST_Controller::HTTP_OK);      
          }          
        }
      }
    }
    else // Ottengo i dati dell'utente
    {
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
        $this->response(array('response' => 'ERR', 'message' => 'Utente non valido.'), REST_Controller::HTTP_OK);
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
    if(isset($patch_data['citizenship']) && !empty($patch_data['citizenship'])) $data['citizenship']=$patch_data['citizenship'];
    if(isset($patch_data['education-level']) && !empty($patch_data['education-level'])) $data['education-level']=$patch_data['education-level'];
    if(isset($patch_data['email']) && !empty($patch_data['email'])) $data['email']=$patch_data['email'];
    if(isset($patch_data['firstname']) && !empty($patch_data['firstname'])) $data['firstname']=$patch_data['firstname'];
    if(isset($patch_data['lastname']) && !empty($patch_data['lastname'])) $data['lastname']=$patch_data['lastname'];
    if(isset($patch_data['nickname']) && !empty($patch_data['nickname'])) $data['nickname']=$patch_data['nickname'];
    if(isset($patch_data['sex']) && !empty($patch_data['sex'])) $data['sex']=$patch_data['sex'];
    if(isset($patch_data['location']) && !empty($patch_data['location'])) $data['location']=$patch_data['location'];
    if(isset($patch_data['website']) && !empty($patch_data['website'])) $data['website']=$patch_data['website'];
    if(isset($patch_data['status']) && !empty($patch_data['status'])) $data['status']=$patch_data['status'];
    if(isset($patch_data['biography']) && !empty($patch_data['biography'])) $data['biography']=$patch_data['biography'];

    if(isset($patch_data['password']) && !empty($patch_data['password'])) $data['password']=password_hash($patch_data['password'], PASSWORD_BCRYPT);
    
    if(!empty($data))
    {
      date_default_timezone_set("Europe/Rome"); 
      $data['_updated']= time();    
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
      $email=urldecode($post_data['email']);
      
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
      $nickname=urldecode($post_data['nickname']);
           
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
      if(isset($post_data['email']) && !empty($post_data['email'])) $data['email']=trim(urldecode($post_data['email']));
      if(isset($post_data['registration-type']) && !empty($post_data['registration-type'])) $data['registration-type']=$post_data['registration-type'];
      
      if($data['registration-type']=='org')
      {
        if(isset($post_data['firstname']) && !empty($post_data['firstname'])) $data['firstname']=trim(urldecode($post_data['firstname']));
        if(isset($post_data['lastname']) && !empty($post_data['lastname'])) $data['lastname']='';
      }
      else
      {
        if(isset($post_data['firstname']) && !empty($post_data['firstname'])) $data['firstname']=trim(urldecode($post_data['firstname']));
        if(isset($post_data['lastname']) && !empty($post_data['lastname'])) $data['lastname']=trim(urldecode($post_data['lastname']));
      }
      
      date_default_timezone_set("Europe/Rome"); 
      $time=time();
      $data['_updated']=$time;
      $data['_created']=$time;
      
      if(isset($post_data['password']) && !empty($post_data['password'])) $data['password']=password_hash(urldecode ($post_data['password']), PASSWORD_BCRYPT);
      
      $this->mongo_db->insert('users', $data);
      
      $data=$this->mongo_db->select(array('_id'))->where(array('email' => trim(urldecode($post_data['email']))))->get('users');     
      
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
}