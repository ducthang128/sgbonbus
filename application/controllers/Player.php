<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Player extends CI_Controller{
    public function index($previous = '')    {
        $this->play($previous);
    }

    public function play($previous = ''){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $today = gmdate("Y-m-d", time());
        $this->db->select('clipid,unique_name');
        $this->db->where('date', $today);
        if ($previous != ''){
            $this->db->where('clipid <>', $previous);
        }
        $this->db->or_where('user_level >', 1);
        $list = $this->db->get('schedule')->result_array();

        shuffle($list);
        $data['cliptoplay'] = 'http://'.$_SERVER["HTTP_HOST"] . '/bes/uploadtobus/'. $list[0]['unique_name'] . '.mp4';
        $data['clipid'] = $list[0]['clipid'];
        $this->load->view('busplayer', $data);
    }


    public function play_old($previous = '')    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $curent_hour = intval(gmdate("H", time() + 3600*(7+date("0"))));
        $current_frame = $curent_hour - 5;
        if ($previous == ''){
            $query = $this->db->where(array('active' => 1,'times >' => 0, 'frame' => $current_frame))->get('adsclips');
        } else {
            $query = $this->db->select('times,total_duration,duration')->where(array('crc' => $previous))->get('adsclips')->result_array();
            if (count($query)>0){
                $times_played = intval($query[0]['times']) - 1;
                if ($times_played < 0){
                    $times_played = 0;
                }
                $total_duration_played = intval($query[0]['total_duration']) - intval($query[0]['duration']);
                if ($total_duration_played < 0){
                    $total_duration_played = 0;
                }
                $update_info = array('times' => $times_played,
                                     'total_duration' => $total_duration_played
                                    );
                $this->db->where('crc', $previous)->update('adsclips', $update_info);
            }
            $query = $this->db->where(array('crc !=' => $previous,'active' => 1,'times >' => 0, 'frame' => $current_frame))->get('adsclips');
        }
        if ($query->num_rows() > 0){
            $list = $query->result_array();
            shuffle($list);
            $data['cliptoplay'] = $list[0]['url'] . $list[0]['name'] . '.' . $list[0]['extension'];
            $data['clipid'] = $list[0]['name'];
            $this->load->view('busplayer', $data);
        } else {
            $query = $this->db->where(array('crc' => $previous,'active' => 1,'times >' => 0, 'frame' => $current_frame))->get('adsclips');
            if ($query->num_rows() == 1){
                $list = $query->result_array();
                $data['cliptoplay'] = $list[0]['url'] . $list[0]['name'] . '.' . $list[0]['extension'];
                $data['clipid'] = $list[0]['name'];
                $this->load->view('busplayer', $data);
            }else{
                $this->load->view('busplayer');
            }
        }
    }
}
