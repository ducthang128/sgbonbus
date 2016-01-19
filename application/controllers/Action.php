<?php

/**
 * @author thangvd
 * @copyright 2015
 */
class Action extends CI_Controller {

    public function update_ads_schedule(){
        // crontab -e
        // */5 * * * * php /var/www/html/index.php action update_ads_schedule
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $today = gmdate("Y-m-d", time());
        $DB1 = $this->load->database('default', TRUE);
        $DB1->distinct();
        $DB1->select('date_updated');
        $date_update = $DB1->get('schedule')->row_array();
        if ($date_update['date_updated'] == $today){
            echo 'Database is uptodate';
        }
        else
        {
            $DB2 = $this->load->database('hosting', TRUE);
            $DB2->select('userid, clipid, unique_name');
            $DB2->where('unique_name !=', '');
            $clip_list = $DB2->get('ads_clips')->result_array();

            $DB1->empty_table('schedule');
            $total_records = 0;
            foreach ($clip_list as $clip_item)
            {
                $DB2->select('user_level');
                $DB2->where('user_id', $clip_item['userid']);
                $user_info = $DB2->get('users')->row_array();

                $DB2->select('clipid,busline,date,frame,duration,times');
                $DB2->where('clipid', $clip_item['clipid']);
                $clip_info = $DB2->get('duration_play_day')->result_array();
                $is_customer = (intval($user_info['user_level']) == 1) ? true : false;

                if ($is_customer)
                {
                    foreach ($clip_info as $clip)
                    {
                        $data = array(
                                'user_level' => $user_info['user_level'],
                                'clipid' => $clip['clipid'],
                                'unique_name' => $clip_item['unique_name'],
                                'busline' => $clip['busline'],
                                'date' => $clip['date'],
                                'frame' => $clip['frame'],
                                'duration' => $clip['duration'],
                                'times' => $clip['times'],
                                'date_updated' => $today
                        );
                        $DB1->insert('schedule', $data);
                        $total_records = $total_records + 1;
                    }
                }
                else
                {
                    $data = array(
                            'user_level' => $user_info['user_level'],
                            'clipid' => $clip_item['clipid'],
                            'unique_name' => $clip_item['unique_name'],
                            'date_updated' => $today
                    );
                    $DB1->insert('schedule', $data);
                    $total_records = $total_records + 1;
                }

            }
            echo 'Database have been update. Total records: '.$total_records.' for '.count($clip_list).' clips. ';
        }
    }

    private function get_list_file_old(){
	    $this->load->library('encrypt');
        $this->load->helper('string');
		$rdn_key  = random_string('alnum', 8);
        $store_location = "/var/www/html/bes/uploadtobus/";
        $data['action'] = 'get_list_video';
        //$data['ipaddr'] = $_SERVER['SERVER_ADDR'];
        $data['macaddr'] = $this->getMacLinux();
        //$data['nucname'] = $_SERVER["SERVER_NAME"];
        $query = $this->db->select('crc,times,total_duration,duration')->get('adsclips')->result_array();
        if (count($query)>0){
            $data['clip_duration'] = $query;
        }else{
            $data['clip_duration'];
        }
        $request = json_encode($data);
        $request_cipher = $this->encrypt->encode($request,$rdn_key);
        $param = array(
            'request' => $request_cipher,
            'key' => $rdn_key
        );
        $url = 'http://bes.saigonsolutions.com.vn/index.php/welcome/remote_control/';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, count($param));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $result = curl_exec($ch);
        //die($result);
        curl_close($ch);
        $return_data = $this->encrypt->decode(base64_decode($result),$rdn_key);

        $array_clipID = array(); //Danh sach cac clip dang duoc kich hoat
        if ($this->isJson($return_data))
        {
            $return_data = json_decode($return_data);
            $return_array_data = (array)$return_data;
            foreach ($return_array_data as $clip_item)
            {
                $item = (array)$clip_item;
                array_push($array_clipID,$item);
            }
        }

        $existfile = array(); // danh sach cac file hien co
        if (is_dir($store_location)){
          if ($dh = opendir($store_location)){
            while (($file = readdir($dh)) !== false){
              $file_name_temp = explode(".", $file);
              $extension = end($file_name_temp);
              $file_name = str_replace(".".$extension,"",$file);
              if (!empty($file_name) && $file_name <> 'index'){
                array_push($existfile,$file_name);
              }
            }
            closedir($dh);
          }
        }

        if (count($array_clipID)>0){
            $this->db->where('active', 1)->update('adsclips', array('active' => 0));
            foreach ($array_clipID as $key=>$clip){
                if (!in_array($clip['checksum'], $existfile)){
                    $getfile[$key] = $this->get_remote_file($clip['unique_name'],$clip['filepath'],$clip['checksum'],$store_location);
                }
                $update_info = array('active' => 1,
                                     'frame' => $clip['frame'],
                                     'duration' => $clip['duration'],
                                     'rawSeconds' => $clip['rawSeconds'],
                                     'total_duration' => $clip['total_duration'],
                                     'times' => $clip['times'],
                                     'filename' => $clip['filename'],
                                     'busline' => $clip['busline']
                                     );
                $this->db->where('crc', $clip['checksum'])->update('adsclips', $update_info);
            }
        }
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        echo gmdate("d/m/Y H:i:s", time() + 3600*(7+date("0")));
        if (isset($getfile) && is_array($getfile)){
            if (!$this->input->is_cli_request()){
                echo '</br>Log:';
                var_dump($getfile);
            }
            echo '</br>Done.';
        }else{
            echo '</br>Done with nothing file given.';
            echo '</br>Return data:</br>';
            print_r($array_clipID);
        }

	}

    private function isJson($string) {
        return ((is_string($string) &&
                (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }

    private function bytesToSize1024($bytes) {
        if ($bytes > 0){
            $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
            $e = floor(log($bytes)/log(1024));
            return sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
        }else{
            return 'n/a Byte';
        }
    }

    private function time_elapsed($secs){
        if ($secs > 0 ){
            if ($secs < 1 ){
                $secs = $secs * 1000;
                return ''.$secs.'/1000 giây';
            }else{
                $bit = array(
                    ' năm'        => $secs / 31556926 % 12,
                    ' tuần'        => $secs / 604800 % 52,
                    ' ngày'        => $secs / 86400 % 7,
                    ' giờ'        => $secs / 3600 % 24,
                    ' phút'    => $secs / 60 % 60,
                    ' giây'    => $secs % 60
                    );
                $ret[] = '';
                foreach($bit as $k => $v){
                    if($v > 1)$ret[] = $v . $k;
                    if($v == 1)$ret[] = $v . $k;            }
                array_splice($ret, count($ret)-1, 0, '');
                return join(' ', $ret);
            }
        }else{
            return 'n/a';
        }

    }

    private function microtime_float(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    private function getMacLinux() {
      exec('netstat -ie', $result);
      if(is_array($result)) {
        $iface = array();
        foreach($result as $key => $line) {
          if($key > 0) {
            $tmp = str_replace(" ", "", substr($line, 0, 10));
            if($tmp <> "") {
              $macpos = strpos($line, "HWaddr");
              if($macpos !== false) {
                $iface[] = array('iface' => $tmp, 'mac' => strtolower(substr($line, $macpos+7, 17)));
              }
            }
          }
        }
        return $iface[0]['mac'];
      } else {
        return "notfound";
      }
    }

    private function get_remote_file($filename='',$url = '',$sCRC='',$location=''){
		$this->load->helper('string');
		$tmp_file_name  = random_string('alnum', 8);
        $tmp_dir = sys_get_temp_dir();
        if (!file_exists($tmp_dir)) {
            mkdir($tmp_dir, 0777, true);
        }
        if (!file_exists($location)) {
            mkdir($location, 0777, true);
        }
        $tmp_path = $tmp_dir.'/'.$tmp_file_name;
        if (file_exists($tmp_path)){
            unlink($tmp_path);
        }
        $start_time = $this->microtime_float();
        $getfile = file_put_contents($tmp_path, fopen($url, 'r'));
        $finish_time = $this->microtime_float();
        if ($getfile !== false){
            $speed_doanload = $this->bytesToSize1024($getfile / abs($finish_time-$start_time));
            $capacity = $this->bytesToSize1024($getfile);
            $time_to_get = $this->time_elapsed(abs($finish_time-$start_time));
            if ($this->input->is_cli_request()){
                echo 'Đã tải: '.$filename.', Dung lượng: '.$capacity.', Thời gian: '.$time_to_get.', Tốc độ tải trung bình: '. $speed_doanload . '.     ';
            }else{
                echo 'Đã tải: '.$filename.', Dung lượng: '.$capacity.', Thời gian: '.$time_to_get.'</br>Tốc độ tải trung bình: '. $speed_doanload . 'ps</br>';
            }
            $file_string = file_get_contents($tmp_path);
            $crc = crc32($file_string);
            if ($sCRC == $crc){
                $file_name_temp = explode(".", $filename);
                $extension = end($file_name_temp);
                $new_file_path = strtolower($crc.'.'.$extension);
                $new_file_name = str_replace('.'.$extension,"",$new_file_path);
                rename($tmp_path, $location.$new_file_path);
                $query = $this->db->get_where('adsclips', array('crc' => $crc,'name'=>$new_file_name));
                $file_url = str_replace("/var/www/html","",$location);
                if ($query->num_rows() == 0){
                    $data = array(
                       'crc' => $crc ,
                       'name' => $new_file_name,
                       'extension' => $extension,
                       'path' => $location,
                       'url' => $file_url,
                       'capacity' => $capacity,
                       'downloadspeed' => $speed_doanload.'ps'
                    );
                    $this->db->insert('adsclips', $data);
                }else{
                    $data = array(
                       'extension' => $extension,
                       'path' => $location,
                       'name' => $new_file_name,
                       'url' => $file_url,
                       'capacity' => $capacity,
                       'downloadspeed' => $speed_doanload.'ps'
                    );
                    $this->db->where('crc', $crc);
                    $this->db->update('adsclips', $data);
                }
                return $this->db->last_query();
            }
        }
        return false;
	}
}

?>