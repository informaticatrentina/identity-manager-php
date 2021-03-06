<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/third_party/vendor/autoload.php';
require APPPATH . '/libraries/REST_Controller.php';

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
    // Verifica password
    if(!empty($params) && $user_id==NULL)
    {
      if(isset($params['where']) && !empty($params['where']))
      {
        $where_string = json_decode($params['where'],TRUE);   

        // Il Profile Manager gestische il tutto con array Json mentre le istanze tramite stringa GET - Fuck!
        // é un array json 
        if(json_last_error() == JSON_ERROR_NONE)
        {       
          if(isset($where_string['$or']) && !empty($where_string['$or']))
          {
            $dataprojection=NULL;
           
            if(isset($params['projection']) && !empty($params['projection']))
            { 
              $params['projection'] = str_replace('{', '', $params['projection']); 
              $params['projection'] = str_replace('}', '', $params['projection']);
              $params['projection'] = str_replace('"', '', $params['projection']);             
              
              $dataprojection=explode(":", $params['projection']); 

              if(isset($dataprojection[0]) && $dataprojection[0]=='email') $dataprojection='email';
              if(isset($dataprojection[0]) && $dataprojection[0]=='_id') $dataprojection='_id';             
            }
           
            if(isset($where_string['$or'][0]['_id']))
            {
              $where_conditions=array();
              foreach($where_string['$or'] as $val)
              {                
                if(isset($val['_id'])) $where_conditions[]=new MongoId($val['_id']);                
              }

              if($dataprojection=='email')
              {
                 $data=$this->mongo_db->select(array('_updated','_created','_id','email','type'))->where_in('_id', $where_conditions)->get('users');            
              }
              else $data=$this->mongo_db->where_in('_id', $where_conditions)->get('users');    
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
                  if(isset($value['gdpr_date']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date']=date('Y-m-d H:i:s',$value['gdpr_date']->sec);  
                  } 
                  if(isset($value['gdpr_date_del']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date_del']=date('Y-m-d H:i:s',$value['gdpr_date_del']->sec);  
                  } 
                  if(isset($value['_id']))
                  {
                    $data[$key]['_id']=(string)$value['_id'];
                  }
                  if(isset($data[$key]['type']) && isset($data[$key]['_id']))
                  {
                    $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => $_SERVER['SERVER_NAME'].'/v1/users/'.$data[$key]['_id']));
                    unset($data[$key]['type']);
                  }
                    
                  // Elimino Password e ResetPwd
                  unset($data[$key]['password']);
                  unset($data[$key]['resetpwd']);          
                }
                
                // Count data
                $count=count($data);                
                $links=array('self' => array('title' => 'users', 'href' => $_SERVER['SERVER_NAME'].'/v1/users'), 'parent' => array('href' => $_SERVER['SERVER_NAME'].'/v1', 'title' => 'home'));
                $meta=array('max_results' => 25, 'total' => $count, 'page' => 1); 
                
                return $this->response(array('_items' => $data, '_links' => $links, '_meta' => $meta), REST_Controller::HTTP_OK);                
              }
              else return $this->response(array('response' => 'ERR', '_items' => array(), '_meta' => array(), '_links' => array()), REST_Controller::HTTP_OK);             
            }
            
            if(isset($where_string['$or'][0]['email']))
            {                        
              // Verifico controllo email iosostengo
              if(count($where_string['$or'])==1)
              {
                if(isset($where_string['$or'][0]['email']) && isset($where_string['$or'][0]['status']))
                {
                  
                  $data=$this->mongo_db->where('email', $where_string['$or'][0]['email'])->where('status', (string)$where_string['$or'][0]['status'])->get('users');                  
                }

                // Nel caso di attivazione di un account registrato dopo che il vecchio è stato disabilitato
                if(isset($where_string['$or'][0]['email']))
                {
                  $data=$this->mongo_db->where('email', $where_string['$or'][0]['email'])->where('gdpr_date_del',NULL)->get('users');                  
                }  
              }
              else
              {
                $where_conditions=array();
                foreach($where_string['$or'] as $val)
                {                
                  if(isset($val['email'])) $where_conditions[]=$val['email'];                
                }
                $data=$this->mongo_db->where_in('email', $where_conditions)->get('users');
              }      
  
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
                    $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => $_SERVER['SERVER_NAME'].'/v1/users/'.$data[$key]['_id']));
                    // Elimino Password e ResetPwd
                    unset($data[$key]['password']);
                    unset($data[$key]['resetpwd']);
                }
                
                // Count data
                $count=count($data);                
                $links=array('self' => array('title' => 'users', 'href' => $_SERVER['SERVER_NAME'].'/v1/users'), 'parent' => array('href' => $_SERVER['SERVER_NAME'].'/v1', 'title' => 'home'));
                $meta=array('max_results' => 25, 'total' => $count, 'page' => 1); 
                
                return $this->response(array('_items' => $data, '_links' => $links, '_meta' => $meta), REST_Controller::HTTP_OK);                
              }
              else return $this->response(array('response' => 'ERR', '_items' => array(), '_meta' => array(), '_links' => array()), REST_Controller::HTTP_OK);         
            }
          }
          elseif(!empty($where_string) && isset($where_string['email']) && isset($where_string['password']) && !empty($where_string['email']) && !empty($where_string['password']))
          {            
            $email=urldecode($where_string['email']);
            $password=urldecode($where_string['password']);
          
            $data=$this->mongo_db->where(array('email' => $email))->where('gdpr_date_del',NULL)->get('users');

            if(empty($data))
            {
              return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);              
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
                  return $this->response(array('_items' => $data), REST_Controller::HTTP_OK);                  
                }
                else
                {                  
                  // Hack SB PASSWORD                                    
                  // Se la password è già stata resettata -> ERRORE
                  if(isset($data[0]['resetpwd']) && $data[0]['resetpwd']==1) 
                  {
                    return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);                    
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
                    return $this->response(array('_items' => $data), REST_Controller::HTTP_OK);                    
                  }                  
                }
              }
              else  
              {
                return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);                
              }
            }
          }
          elseif(isset($where_string['email']) && isset($where_string['status']) && !empty($where_string['email']) && !empty($where_string['status'])) // Verifico Username e Stato Attivo
          {
            $email=urldecode($where_string['email']);

                  
            if($where_string['status']==1 || $where_string['status']=='enable') $status=1;
            if($where_string['status']==0 || $where_string['status']=='disable') $status=0;
       
            $data=$this->mongo_db->where(array('email' => $email))->get('users');
                       
            if(empty($data))
            {
              return $this->response(array('response' => 'ERR', '_items' => $data), REST_Controller::HTTP_OK);              
            }
            else
            {
              $result=array();       
              foreach ($data as $single_data)
              {
                if($single_data['status']==$status)
                {
                  $result[]=$single_data;
                }                
              }      
              return $this->response(array('_items' => $result), REST_Controller::HTTP_OK);            
            }
          }    
          elseif(!empty($where_string) && isset($where_string['email']) && !empty($where_string['email']))              // Verifico Username
          {           
            $email=urldecode($where_string['email']);   
  
            $data=$this->mongo_db->where(array('email' => $email))->get('users');
                    
            if(empty($data))
            {
              return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);            
            }
            else
            { 
              // Verifico che non vi sia un account con status=1      
              if(is_array($data))  
              {
                $active_user=false;
                $active_key=null;
                foreach($data as $key => $single)
                {
                  if(!isset($single['gdpr_date_del'])) { $active_user=TRUE; $active_key=$key; }                  
                }
              }    

              if($active_user)
              {
                if(isset($data[$active_key]['password'])) unset($data[$active_key]['password']);
                if(isset($data[$active_key]['_id'])) $data[$active_key]['_id']=(string)$data[$active_key]['_id'];  
                $final_data[]=$data[$active_key];      
                return $this->response(array('_items' => $final_data), REST_Controller::HTTP_OK);        
              }
              else
              {
                return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK); 
              }                    
            }
          }
          elseif(!empty($where_string) && isset($where_string['_id']) && !empty($where_string['_id']))          // Verifico _id
          {           
            try
            {
              $mongo_user_id = new MongoId($where_string['_id']);
            }
            catch (MongoException $ex)
            {
              return $this->response(array('response' => 'ERR', 'message' => 'Utente ID non valido.'), REST_Controller::HTTP_OK);
              
            }
            $data=$this->mongo_db->where(array('_id' => $mongo_user_id))->get('users');

            if(empty($data))
            {
              return $this->response(array('response' => 'ERR', '_items' => $mongo_user_id), REST_Controller::HTTP_OK);              
            }
            else
            {
              // Converto il campo _id in string
              $data[0]['_id']=(string)$data[0]['_id'];
              // Non trasmetto la password
              unset($data[0]['password']);
              $response_arr=$data[0];
              return $this->response(array('_items' => $data), REST_Controller::HTTP_OK);              
            }
          }        
          elseif(!empty($where_string) && isset($where_string['nickname']) && !empty($where_string['nickname'])) // Verifico Nickname
          {            
            $nickname=urldecode($where_string['nickname']);
           
            $data=$this->mongo_db->where(array('nickname' => $nickname))->get('users');
          
            if(empty($data))
            {
             return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);              
            }
            else
            {                
              // Converto il campo _id in string            
              $data[0]['_id']=(string)$data[0]['_id'];
              return $this->response(array('_items' => $data[0]['_id']), REST_Controller::HTTP_OK);               
            }          
          }

          elseif(!empty($where_string) && isset($where_string['firstname']) && !empty($where_string['firstname'])) // Verifico Nome organizzazione
          {
              $firstname=urldecode($where_string['firstname']);
              $datar=$this->mongo_db->where(array('firstname' => $firstname,'type'=>'org'))->get('users');
              if(!empty($datar))
              {
                $error_status='ERR';
                $error_issues=array('firstname' => 'is not unique for organizations');
                $this->response(array('_status' => $error_status, '_issues' => $error_issues), REST_Controller::HTTP_OK);
              }
              else
              {
                $this->response(array('_status' => 'OK', '_issues' => null), REST_Controller::HTTP_OK);
              }
          
          }
          elseif(!empty($where_string) && isset($where_string['source']) && !empty($where_string['source'])) // Verifico Source
          {
            $source=urldecode($where_string['source']);
            $data=$this->mongo_db->where(array('source' => $source))->get('users');

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
              if(isset($value['gdpr_date']))
              {                                                               
                date_default_timezone_set('Europe/Rome');                        
                $data[$key]['gdpr_date']=date('Y-m-d H:i:s',$value['gdpr_date']->sec);  
              } 
              if(isset($value['gdpr_date_del']))
              {                                                               
                date_default_timezone_set('Europe/Rome');                        
                $data[$key]['gdpr_date_del']=date('Y-m-d H:i:s',$value['gdpr_date_del']->sec);  
              } 
              if(isset($value['_id']))
              {
                $data[$key]['_id']=(string)$value['_id'];
              }
              if(isset($data[$key]['type']) && isset($data[$key]['_id']))
              {
                $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => $_SERVER['SERVER_NAME'].'/v1/users/'.$data[$key]['_id']));
                unset($data[$key]['type']);
              }
                    
              // Elimino Password e ResetPwd
              unset($data[$key]['password']);
              unset($data[$key]['resetpwd']);          
            }

            if(empty($data))
            {
              return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);              
            }
            else
            {                
                return $this->response(array('_items' => $data), REST_Controller::HTTP_OK);            
            }     
          }
          else
          {            
            return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);                       
          }
        }
        else
        {         
          $dataprojection=NULL;
          // Remove Refuso modulo Python char \x22
          if(isset($params['where']) && is_string($params['where']))
          {   
            $params['where'] = str_replace('\x22', '', $params['where']);            
          }

          if(isset($params['projection']) && is_string($params['projection']))
          {   
            $params['projection'] = str_replace('\x22', '', $params['projection']);
            $params['projection'] = str_replace('{', '', $params['projection']); 
            $params['projection'] = str_replace('}', '', $params['projection']);   
            $dataprojection=explode(":", $params['projection']); 

            if(isset($dataprojection[0]) && $dataprojection[0]=='email') $dataprojection='email';
            if(isset($dataprojection[0]) && $dataprojection[0]=='_id') $dataprojection='_id';             
          }

          // Richiesta $or:
          if(preg_match('/(or:){1}/',$params['where']))
          {
            $params['where'] = str_replace('$or:', '', $params['where']);    
            $params['where'] = str_replace('{[', '', $params['where']);                
            $params['where'] = str_replace(']}', '', $params['where']); 
            $params['where'] = str_replace('{', '', $params['where']); 
            $params['where'] = str_replace('}', '', $params['where']);    
            $params['where'] = str_replace(',', '', $params['where']);   

            // Ricerca tramite _id
            if(preg_match('/(_id){1}/',$params['where']))
            {
              $params['where'] = str_replace('_id', '', $params['where']); 
              $data=explode(":", $params['where']);              
              $clean_data=array_filter($data);
              $final_data=array_values($clean_data);

              $where_conditions=array();

              foreach($final_data as $val)
              {
                $where_conditions[]=new MongoId($val);                      
              }

              if($dataprojection=='email')
              {
                 $data=$this->mongo_db->select(array('_updated','_created','_id','email','type'))->where_in('_id', $where_conditions)->get('users');            
              }
              else $data=$this->mongo_db->where_in('_id', $where_conditions)->get('users');    

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
                  if(isset($value['gdpr_date']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date']=date('Y-m-d H:i:s',$value['gdpr_date']->sec);  
                  } 
                  if(isset($value['gdpr_date_del']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date_del']=date('Y-m-d H:i:s',$value['gdpr_date_del']->sec);  
                  } 
                  if(isset($value['_id']))
                  {
                    $data[$key]['_id']=(string)$value['_id'];
                  }
                  if(isset($data[$key]['type']) && isset($data[$key]['_id']))
                  {
                    $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => $_SERVER['SERVER_NAME'].'/v1/users/'.$data[$key]['_id']));
                    unset($data[$key]['type']);
                  }
                    
                  // Elimino Password e ResetPwd
                  unset($data[$key]['password']);
                  unset($data[$key]['resetpwd']);          
                }
                
                // Count data
                $count=count($data);                
                $links=array('self' => array('title' => 'users', 'href' => $_SERVER['SERVER_NAME'].'/v1/users'), 'parent' => array('href' => $_SERVER['SERVER_NAME'].'/v1', 'title' => 'home'));
                $meta=array('max_results' => 25, 'total' => $count, 'page' => 1); 
                
                return $this->response(array('_items' => $data, '_links' => $links, '_meta' => $meta), REST_Controller::HTTP_OK);                
              }
              else return $this->response(array('response' => 'ERR', '_items' => array(), '_meta' => array(), '_links' => array()), REST_Controller::HTTP_OK);
            }

            if(preg_match('/(email){1}/',$params['where']))
            {
              $params['where'] = str_replace('email', '', $params['where']); 
              $data=explode(":", $params['where']);              
              $clean_data=array_filter($data);
              $final_data=array_values($clean_data);

              $where_conditions=array();
              foreach($final_data as $val)
              {
                $where_conditions[]=$val;                      
              }
  
              $data=$this->mongo_db->where_in('email', $where_conditions)->get('users');        
              
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
                  if(isset($value['gdpr_date']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date']=date('Y-m-d H:i:s',$value['gdpr_date']->sec);  
                  } 
                  if(isset($value['gdpr_date_del']))
                  {                                                               
                    date_default_timezone_set('Europe/Rome');                        
                    $data[$key]['gdpr_date_del']=date('Y-m-d H:i:s',$value['gdpr_date_del']->sec);  
                  } 
                  if(isset($value['_id']))
                  {
                    $data[$key]['_id']=(string)$value['_id'];
                  }
                    $data[$key]['_links']=array('self' => array('title' => $data[$key]['type'], 'href' => $_SERVER['SERVER_NAME'].'/v1/users/'.$data[$key]['_id']));
                    // Elimino Password e ResetPwd
                    unset($data[$key]['password']);
                    unset($data[$key]['resetpwd']);
                }

                // Count data
                $count=count($data);                
                $links=array('self' => array('title' => 'users', 'href' => $_SERVER['SERVER_NAME'].'/v1/users'), 'parent' => array('href' => $_SERVER['SERVER_NAME'].'/v1', 'title' => 'home'));
                $meta=array('max_results' => 25, 'total' => $count, 'page' => 1); 
                
                return $this->response(array('_items' => $data, '_links' => $links, '_meta' => $meta), REST_Controller::HTTP_OK);                
              }
              else return $this->response(array('response' => 'ERR', '_items' => array(), '_meta' => array(), '_links' => array()), REST_Controller::HTTP_OK);              
            }
          }
          else
          {
            $data=explode(",", $params['where']); 
            
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

          if(count($data)==1 && isset($data[0]) && !empty($data[0]))
          {
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
          return $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
          }
        }
      }
      elseif(isset($params['_id']) && !empty($params['_id']))
      {
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
        $this->response(array('response' => 'ERR', '_items' => array()), REST_Controller::HTTP_OK);
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
    if(isset($patch_data['status'])) $data['status']=$patch_data['status'];    
    if(isset($patch_data['gdpr'])) $data['gdpr']=$patch_data['gdpr'];    
    if(isset($patch_data['gdpr_date'])) $data['gdpr_date']=new MongoDate(strtotime($patch_data['gdpr_date']));
    if(isset($patch_data['gdpr_date_del'])) $data['gdpr_date_del']=new MongoDate(strtotime($patch_data['gdpr_date_del']));
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
      
      // In caso di attivazione o riattivazione vado a eliminare le informazioni relative alla gdpr
      if(isset($patch_data['status']) && $patch_data['status']==1)
      {
        $this->mongo_db->where(array('_id' => new MongoId($user_id)))->unset_field('gdpr_date_del')->update('users'); 
      }
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
      
      $data=$this->mongo_db->where(array('email' => $email, 'status' => 1))->get('users');     
          
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
      if(isset($post_data['site-user-info']) && !empty($post_data['site-user-info'])) $data['site-user-info']=$post_data['site-user-info'];
      if(isset($post_data['profile-info']) && !empty($post_data['profile-info'])) $data['profile-info']=$post_data['profile-info'];

      if(isset($post_data['gdpr'])) $data['gdpr']=$post_data['gdpr'];    
      if(isset($post_data['gdpr_date'])) $data['gdpr_date']=new MongoDate(strtotime($post_data['gdpr_date']));
      if(isset($post_data['gdpr_date_del'])) $data['gdpr_date_del']=new MongoDate(strtotime($post_data['gdpr_date_del']));
      
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
