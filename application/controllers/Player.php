<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Player extends CI_Controller{
    public function index($previous = '')    {
        $this->play($previous);
    }

    public function play($previous = ''){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $today = gmdate("Y-m-d", time());
        //frame 0 -> 05h00-05h59
        //frame 2 -> 07h00-07h59
        $frame_offset = -1;
        $hour = date('h', time());
        $current_frame = $hour + $frame_offset;

        $this->db->select('clipid,unique_name');
        $this->db->where('date', $today);
        $this->db->where('frame', $current_frame);
        if ($previous != ''){
            $this->db->where('clipid <>', $previous);
        }
        $this->db->or_where('user_level >', 1);
        $list = $this->db->get('schedule')->result_array();

        shuffle($list);
        $data['cliptoplay'] = 'http://'.$_SERVER["HTTP_HOST"] . '/bes/uploadtobus/'. $list[0]['unique_name'] . '.mp4';
        $data['clipid'] = $list[0]['clipid'];

        $ma_so_xe = $_SERVER["SERVER_NAME"];
        $DB2 = $this->load->database('hosting', TRUE);
        $DB2->delete('quang_cao_dang_phat', array('so_xe' => $ma_so_xe));
        $update_data = array(
                'ma_clip' => $list[0]['clipid'],
                'ten_clip' => $list[0]['unique_name'],
                'so_xe' => $ma_so_xe
        );
        $DB2->insert('quang_cao_dang_phat', $update_data);

        $this->load->view('busplayer', $data);
    }
}
