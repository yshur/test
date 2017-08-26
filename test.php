<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Jerusalem');
//initialization const
$alert_test = 0;
$fileName = '/home/ec2-user/test/tests.log';
$newJson = test();
$time = date("m-d-Y H:i:s");
$outArray = array ('DATE'=>$time,'results'=>$newJson);	
file_put_contents ($fileName, json_encode($outArray)."\n",FILE_APPEND | LOCK_EX);
if($alert_test > 0) 
	sendEmail(json_encode($outArray));
//echo json_encode($outArray);
//print_r ($newJson);
//end of the script

function test_ec2($checkName, $testCommand) {
        $line = "";
	$alert = false;
	global $alert_test;
        exec($testCommand, $result);
        $line = preg_replace('/\s+/', ' ', trim($result[4]));//replace multi white space with one
        $output = explode(" ", $line); //split the line
        $result = "tries: $output[2], Err: $output[14] $output[15], Avg: $output[8], Min: $output[10], Max: $output[12]";
	exec("aws cloudwatch put-metric-data --metric-name error_$checkName --namespace $checkName --value $output[14]");
	exec("aws cloudwatch put-metric-data --metric-name Average_$checkName --namespace $checkName --value $output[8]");
	exec("aws cloudwatch put-metric-data --metric-name Min_$checkName --namespace $checkName --value $output[10]");
	exec("aws cloudwatch put-metric-data --metric-name Max_$checkName --namespace $checkName --value $output[12]");
	exec("aws cloudwatch put-metric-data --metric-name Tries_$checkName --namespace $checkName --value $output[2]");
        if((intval($output[14]) > 0) || (intval($output[8]) > 1500))  {
                $alert = true;
		$alert_test++;
	}
        $outArray = array('CheckName'=>$checkName, 'Alerted'=>($alert)?'true':'false', 'result'=>$result);
        return $outArray;
}

function test() {
        $test_command = array("DNS1" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/NODE1_1.jmx -l /tmp/NODE1results.jtl',
			"DNS2" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/NODE2_1.jmx -l /tmp/NODE2results.jtl',
			"LoadBalancer" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/LOAD_BALANCER_1.jmx -l /tmp/LBresults.jtl'
					);
        $result_array = array();        
        $i = 0;
        foreach($test_command as $checkName=>$cmd)   {
			$result_array[$i] = test_ec2($checkName, $cmd);
			$i++;
		}
        return $result_array;
}

function sendEmail($result_array)    {

	$to="yair.shur@gmail.com";
	$headers = 'From: testerAWS@aws.com' . "\r\n";
	$subject = "ALERT: Problem with NODE servers";
	mail($to, $subject, $result_array, $headers);
}
?>

