<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EmailLibrary
 * 
 * @author Stefano Beccalli
 * @copyright JLB Books sas
 * @version 1.0 - 22/10/2014
 * @access public
 */
class EmailLibrary
{
	protected $_CI=NULL;
  private $_config=array('protocol'     => 'smtp',
      'smtp_host'    => 'smtp.jlbbooks.it',
      'smtp_port'    => 25,
      'smtp_user'    => 'valentina.darold@jlbbooks.it',
      'smtp_pass'    => '4X73!qaz',
      'smtp_timeout' => '4',
      'mailtype'     => 'html',
      'charset'      => 'utf-8',
      'crlf'         => '\r\n',
      'newline'      => '\r\n');


  /**
   * EmailLibrary::__construct()
   */
  public function __construct()
  {
    $config           = Array(
        'protocol'     => 'smtp',
        'smtp_host'    => 'smtp.jlbbooks.it',
        'smtp_port'    => 25,
        'smtp_user'    => 'valentina.darold@jlbbooks.it',
        'smtp_pass'    => '4X73!qaz',
        'smtp_timeout' => '4',
        'mailtype'     => 'html',
        'charset'      => 'utf-8',
        'crlf'         => '\r\n',
        'newline'      => '\r\n'
    );

   	$this->_CI = &get_instance();
    $this->_CI->load->library('email',$config);
	}

  public function inizialize($mail_config)
  {
    if(empty($mail_config) || !is_array($mail_config)) throw new Exception('EmailLibrary->inizialize :: Attenzione la variabile mail_config risulta vuota o non valida. Valore: '.var_dump($mail_config), 1);
    $mail_protocol=array('mail','smtp','sendmail');
    if(!array_key_exists('protocol', $mail_config) || !in_array($mail_config['protocol'],  $mail_protocol)) throw new Exception('EmailLibrary->inizialize :: Attenzione, la variabile protocol risulta non presente o non valida. Valore: '.var_dump($mail_config), 1);

    if($mail_config['protocol']=='sendmail' && !array_key_exists('mailpath',$mail_config)) throw new Exception('EmailLibrary->inizialize :: Attenzione, la variabile mailpath è obbligatoria se hai scelto come protocollo sendmail. Valore: '.var_dump($mail_config), 1);
    if($mail_config['protocol']=='smtp' && !array_key_exists('smtp_host',$mail_config)  && !array_key_exists('smtp_user',$mail_config) && !array_key_exists('smtp_pass',$mail_config) && !array_key_exists('smtp_port',$mail_config)) throw new Exception('EmailLibrary->inizialize :: Attenzione, le variabili smtp_host, smtp_user, smtp_pass sono obbligatorie se hai scelto come protocollo smtp. Valore: '.var_dump($mail_config), 1);

    if(!array_key_exists('charset', $mail_config)) $this->_config['charset']='utf8';

    foreach($mail_config as $mail_key => $mail_value)
    {
      $this->_config[$mail_key]=$mail_value;
    }
    $this->_CI->email->initialize($this->_config);
  }

  public function send($mail_data)
  {
    if(empty($mail_data) || !is_array($mail_data)) throw new Exception('EmailLibrary->send :: Attenzione la variabile mail_data risulta vuota o non valida. Valore: '.var_dump($mail_data), 1);
    
    if(!array_key_exists('mail_from', $mail_data)) throw new Exception('EmailLibrary->send :: Attenzione, la variabile mail_from risulta non presente o non valida. Valore: '.var_dump($mail_data), 1);
    if(!array_key_exists('mail_from_name', $mail_data)) throw new Exception('EmailLibrary->send :: Attenzione, la variabile mail_from risulta non presente o non valida. Valore: '.var_dump($mail_data), 1);
    if(!array_key_exists('mail_to', $mail_data)) throw new Exception('EmailLibrary->send :: Attenzione, la variabile mail_to risulta non presente o non valida. Valore: '.var_dump($mail_data), 1);
    if(!array_key_exists('subject', $mail_data)) throw new Exception('EmailLibrary->send :: Attenzione, la variabile mail_to risulta non presente o non valida. Valore: '.var_dump($mail_data), 1);
    if(!array_key_exists('message', $mail_data)) throw new Exception('EmailLibrary->send :: Attenzione, la variabile mail_to risulta non presente o non valida. Valore: '.var_dump($mail_data), 1);

    $this->_CI->email->from($mail_data['mail_from'], $mail_data['mail_from_name']);
    $this->_CI->email->to($mail_data['mail_to']);
    $this->_CI->email->subject($mail_data['subject']);
    $this->_CI->email->message($mail_data['message']);
    $this->_CI->email->send();
  }
}