<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @package	Aggregator
 * @author	Stefano Beccalli
 * @copyright	Copyright (c) 2016
 * @link	http://www.jlbbooks.it
 * @since	Version 1.0.0
 */
class Index extends CI_Controller
{
    function __construct()
    {
      parent::__construct();  
    }
    
    public function index()
    {
      echo "Identity Manager";  
    }   
}
