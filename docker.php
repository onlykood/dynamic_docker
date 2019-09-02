<?php
define("IDSFILE", "../dockerID");
define("USEDOCKER", "../UseDocker");
define("MANAGE", "../dockerManage ");
define("TOKEN", "WPSECWEBTEST");
define("MINPORT", 50000);
define("MAXPORT", 59999);
define("MAXNUM", 20);
define("EXISTTIME", 3600);
define("DEBUG", false);

function returnInfo($text = "Null", $code = '0',$data='') {
	$content = array(
		array("code" => $code, "text" => $text),
		$data
	);
	die(json_encode($content));
}

# check token and docker id
if (!isset($_GET['token']) or $_GET['token'] != TOKEN) {
	header("HTTP/1.0 404 Not Found");
	die();
}

isset($_GET['action']) or returnInfo("No action!");

if($_GET['action']=='create')
{
	isset($_GET['dockerID']) or returnInfo('No docker id');
	$id = intval($_GET['dockerID']);

	# default open 80 port
	$dockerPort = $_GET['port'] ?? '80';

	# Check id in docker id list 
	file_exists(IDSFILE) or returnInfo("No such docker id list file");
	$dockerInfo = json_decode(file_get_contents(IDSFILE), true);
	array_key_exists($id, $dockerInfo) or returnInfo("id not in docker id list");

	# check use docker exist
	file_exists(USEDOCKER) or returnInfo("No such use docker list file");
	$useInfo = json_decode(file_get_contents(USEDOCKER), true);
	exec(MANAGE . 'docker ps -aq', $ContainerID);
	foreach ($useInfo as $key => $value) {
		if (!in_array($value, $ContainerID))
			unset($useInfo[$key]);
	}

	# get rand port , name and Check them
	$port = mt_rand(MINPORT, MAXPORT);
	$randName = md5(sha1(uniqid('', true) . mt_rand(1000000000, 9999999999)));
	$i = 100;
	count($useInfo) < MAXNUM or returnInfo("docker max", 8);
	while (array_key_exists($port, $useInfo) and $i) {
		$port = mt_rand(MINPORT, MAXPORT);
		$i--;
	}
	$i == 0 and returnInfo("no luck...");
	$useInfo[$port] = $randName;

	# docker run
	$dockerName = $dockerInfo[$id];
	$command = MANAGE . "docker run -tid -p $port:$dockerPort --name $randName $dockerName";
	$data = exec($command);
	preg_match("/^\w{64}$/", $data) or returnInfo("docker run error");

	# write randName file
	file_put_contents($randName, MANAGE . "docker rm -f " . $randName);
	if (!file_exists($randName)) {
		$command = MANAGE . "docker rm -f $randName";
		exec($command);
		returnInfo("create file fail, maybe permission denied");
	}
	$createTime = time();
	# make timed task
	$command = MANAGE . "at -f $randName " . date("H:i", $createTime + EXISTTIME);
	exec($command, $tmps);

	unlink($randName);

	# write use docker info
	$useInfo[$port] = substr($data, 0, 12);
	file_put_contents(USEDOCKER, json_encode($useInfo));

	# all ok
	$data = array(
		'port' => $port,
		'dockerName' => $randName,
		'createTime' => $createTime
	);
	returnInfo("create OK", 1,  $data);
}
elseif($_GET['action']=='destroy'){
	$dockerName=$_GET['dockerName'];
	$command=MANAGE."docker rm -f ".$dockerName;
	exec($command);
	returnInfo("destroy OK!",1);
}
else{
	returnInfo("error!");
}
