<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {
	public function index()
	{
		$this->load->view('welcome_message');
	}

    public function remote_control($request=''){
        if (isset($_POST['request']) && isset($_POST['key'])){
            $this->load->library('encrypt');
            $request_data = $this->encrypt->decode($_POST['request'],$_POST['key']);
            switch ($request_data){
            	case 'get_list_video':
                    $this->load->model('ads_content_model');
                    $list = $this->ads_content_model->list_clip_to_bus();
                    die($list);
            	break;


            	default :
                    die('Unknow command');
            }
        }else{
            die();
        }
	}
}
