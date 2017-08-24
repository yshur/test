<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

//initialization const
$fileName = '~/tests.log';
$newJson = test();
file_put_contents ($fileName ,$newJson);
//end of the script

function test_ec2($checkName, $testCommand) {
	$line = "";
	$alert = false;
	exec($testCommand, $result);
	$line = preg_replace('/\s+/', ' ', trim($result[4]));//replace multi white space with one
	$output = explode(" ", $line); //split the line
	$result = "tries: $output[2], Err: $output[14] $output[15], Avg: $output[8], Min: $output[10], Max: $output[12]"
	if(((intval($output[14]) > 0) || (intval($output[8]) > 1500))
		$alert = true;
	$time = date("m-d-Y H:i:s");
	$outArray = array('DATE'=>$time,'CheckName'=>$checkName, 'Alerted'=>$alert, 'result'=>$result);		
	return $outArray;
}

function test(){
	$test_command = array("DNS1_1" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/NODE1_1.jmx -l /tmp/NODE1results.jtl',
						"DNS2_1" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/NODE2_1.jmx -l /tmp/NODE2results.jtl',
						"LOAD_BALANCER_1" => 'sudo /opt/jmeter/apache-jmeter-3.2/bin/jmeter -n -t /opt/jmeter/apache-jmeter-3.2/bin/LOAD_BALANCER_1.jmx -l /tmp/LBresults.jtl'
					);
	$result_array = array();
	$alert_test = false;
	$i = 0;
	foreach($test_command as $checkName=>$cmd)
        $result_array[$i] = test_ec2($checkName, $cmd);
		if($result_array[$i]['Alerted'] == true)
			$alert_test = true;
		$i++;
    }
	if($alert_test)
		sendEmail($result_array);
	return $result_array;
}

function sendEmail($result_array)    {
	
	$to="yair.shur@gmail.com";
    $headers = 'From: testerAWS@aws.com' . "\r\n";
	$subject = "ALERT: Problem with NODE servers";
	$body = $result_array[0]."\n".$result_array[1]."\n".$result_array[2]."\n";
    mail($to, $subject, $body, $headers);
}
?>
