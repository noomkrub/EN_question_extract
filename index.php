<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
*/

if(!$_REQUEST['m']){
	echo "<form method='post' action='?'>";
	echo "<input type='hidden' name='m' value='fetch'>";
	echo "EN public token <input name='en_token'><br>";
	echo "is_new ID <input name='is_new'><br>";
	echo "first_action ID <input name='first_action'><br>";
	echo "last_action ID <input name='last_action'><br>";
	echo "<input type='submit' value='Get it'>";
	}
elseif ($_REQUEST['m']=="fetch") {
	
	if(!($_REQUEST['is_new']&&$_REQUEST['first_action']&&$_REQUEST['last_action'])){echo "Please give correct information";exit;}
	$en_token=$_REQUEST['en_token'];
	$q_list['is_new']=$_REQUEST['is_new'];
	$q_list['first_action']=$_REQUEST['first_action'];
	$q_list['last_action']=$_REQUEST['last_action'];

// Full URL to fetch
//$url='http://e-activist.com/ea-dataservice/data.service?service=EaSupporterQuestionResponse&token='.$en_token.'&contentType=json&questionId=';

$datasource="https://e-activist.com/ea-dataservice/data.service";
$settings="service=EaSupporterQuestionResponse&token=$en_token&contentType=json&questionId=";
$is_new_setting=$settings.$q_list['is_new'];
$frist_action_setting=$settings.$q_list['first_action'];
$last_action_setting=$settings.$q_list['last_action'];


// Prepre to fetch all question
// Initial curl request
$ch1 = curl_init();
$ch2 = curl_init();
$ch3 = curl_init();
// set config for each call
curl_setopt($ch1, CURLOPT_URL, $datasource);
curl_setopt($ch1, CURLOPT_HEADER, 0);
curl_setopt($ch1, CURLOPT_POSTFIELDS, $is_new_setting);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch1, CURLOPT_TIMEOUT,600);

curl_setopt($ch2, CURLOPT_URL, $datasource);
curl_setopt($ch2, CURLOPT_HEADER, 0);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $frist_action_setting);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch2, CURLOPT_TIMEOUT,600);

curl_setopt($ch3, CURLOPT_URL, $datasource);
curl_setopt($ch3, CURLOPT_HEADER, 0);
curl_setopt($ch3, CURLOPT_POSTFIELDS, $last_action_setting);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch3, CURLOPT_TIMEOUT,600);

// prepare multi curl
$mh = curl_multi_init();
curl_multi_add_handle($mh,$ch1);
curl_multi_add_handle($mh,$ch2);
curl_multi_add_handle($mh,$ch3);

       $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
   
        while ($active && $mrc == CURLM_OK) {
            // Wait for activity on any curl-connection
            if (curl_multi_select($mh) == -1) {
                usleep(1);
            }
   
            // Continue to exec until curl is ready to
            // give us more data
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

// close curl
curl_multi_remove_handle($mh, $ch1);
curl_multi_remove_handle($mh, $ch2);
curl_multi_remove_handle($mh, $ch3);
curl_multi_close($mh);
// retreive data as variable
$is_new_data = json_decode(curl_multi_getcontent($ch1),TRUE);
$first_action_data = json_decode(curl_multi_getcontent($ch2),TRUE);
$last_action_data = json_decode(curl_multi_getcontent($ch3),TRUE);
// start to make it all understandable

/*Columns value returned
0->firstName
1->city
2->region
3->postcode
4->country
5->questionId
6->supporterId
7->response
*/
$is_new_count=count($is_new_data['rows']);
$first_action_count=count($first_action_data['rows']);
$last_action_count=count($last_action_data['rows']);

//convert all text object data to be a single variable for easier to process
for ($i=0;$i<$is_new_count;$i++){
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['firstName']=$is_new_data['rows'][$i]['columns'][0]['value'];
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['city']=$is_new_data['rows'][$i]['columns'][1]['value'];
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['region']=$is_new_data['rows'][$i]['columns'][2]['value'];
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['postcode']=$is_new_data['rows'][$i]['columns'][3]['value'];
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['country']=$is_new_data['rows'][$i]['columns'][4]['value'];
	$data[$is_new_data['rows'][$i]['columns'][6]['value']]['is_new']=$is_new_data['rows'][$i]['columns'][7]['value'];
	if ($is_new_data['rows'][$i]['columns'][7]['value']=='y'){$is_new['y']++;}
	elseif ($is_new_data['rows'][$i]['columns'][7]['value']=='n'){$is_new['n']++;}
	}
// Add first_action from result
for ($i=0;$i<$first_action_count;$i++){
	$key=$first_action_data['rows'][$i]['columns'][6]['value'];
	$f_val=$first_action_data['rows'][$i]['columns'][7]['value'];
	if (!$data[$key]['firstName']){$data[$key]['firstName']=$first_action_data['rows'][$i]['columns'][0]['value'];}
	if (!$data[$key]['city']){$data[$key]['city']=$first_action_data['rows'][$i]['columns'][1]['value'];}
	if (!$data[$key]['region']){$data[$key]['region']=$first_action_data['rows'][$i]['columns'][2]['value'];}
	if (!$data[$key]['postcode']){$data[$key]['postcode']=$first_action_data['rows'][$i]['columns'][3]['value'];}
	if (!$data[$key]['country']){$data[$key]['country']=$first_action_data['rows'][$i]['columns'][4]['value'];}
	$data[$key]['first_action']=$f_val;
// 55444 2016-10-05t06:28:07.857z newsletter-general email gpmyem-03 nexus 2016-09
// 0: campaignid , 1: date time , 2: source medium campaign content
	if ($f_val){
		$f_val_s=explode(' ',$f_val);
		if ($f_val_s[0]!='-'){
			$datetime=explode('t',$f_val_s[1]);
			$first_date[$datetime[0]]++;
			$first[$f_val_s[0]]++;
			}
		}
	}
// Add last_action from result
for ($i=0;$i<$last_action_count;$i++){
	$key=$last_action_data['rows'][$i]['columns'][6]['value'];
	$l_val=$last_action_data['rows'][$i]['columns'][7]['value'];
	if (!$data[$key]['firstName']){$data[$key]['firstName']=$first_action_data['rows'][$i]['columns'][0]['value'];}
	if (!$data[$key]['city']){$data[$key]['city']=$first_action_data['rows'][$i]['columns'][1]['value'];}
	if (!$data[$key]['region']){$data[$key]['region']=$first_action_data['rows'][$i]['columns'][2]['value'];}
	if (!$data[$key]['postcode']){$data[$key]['postcode']=$first_action_data['rows'][$i]['columns'][3]['value'];}
	if (!$data[$key]['country']){$data[$key]['country']=$first_action_data['rows'][$i]['columns'][4]['value'];}
	$data[$key]['last_action']=$l_val;
	if ($l_val){
		$l_val_s=explode(' ',$l_val);
		if ($l_val_s[0]!='-'){
			$datetime=explode('t',$l_val_s[1]);
			$last_date[$datetime[0]]++;
			$last[$l_val_s[0]]++;
			}
		}


//	$l_val_s=explode(' ',$l_val);
//	if (isset($l_val_s[0])){$last[$l_val_s[0]]++;}
	}
//var_dump($data);
//$count=count($data);
// display count of each isnew
echo "new=y ".$is_new['y']."<br>";
echo "new=n ".$is_new['n']."<br>";

// display count of first campaign
foreach ($first as $key=>$val){
	echo "first campaign $key = $val<br>";
	}
echo "<br>";

// display count of last campaign
foreach ($last as $key=>$val){
	echo "last campaign $key = $val<br>";
	}
echo "<br>";

// display count of first campaign date
foreach ($first_date as $key=>$val){
	echo "First in $key = $val<br>";
	}
echo "<br>";

// display count of lat campaign date
foreach ($last_date as $key=>$val){
	echo "Last in $key = $val<br>";
	}
echo "<br>";

echo "<table border=\"1\"><tr><td>supporterId</td><td>firstName</td><td>city</td><td>region</td><td>postcode</td><td>country</td><td>is_new</td><td>first_action</td><td>last_action</td></tr>";
foreach($data as $key=>$contain){
	echo "<tr>";
	echo "<td>".$key."</td>";
	echo "<td>".$contain['firstName']."</td>";
	echo "<td>".$contain['city']."</td>";
	echo "<td>".$contain['region']."</td>";
	echo "<td>".$contain['postcode']."</td>";
	echo "<td>".$contain['country']."</td>";
	echo "<td>".$contain['is_new']."</td>";
	echo "<td>".$contain['first_action']."</td>";
	echo "<td>".$contain['last_action']."</td>";
	echo "</tr>";
	}
echo "</table>";


}
else {echo "Please give correct information";}

?>
