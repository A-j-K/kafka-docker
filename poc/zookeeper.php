<?php

$zookeeper = new Zookeeper('10.115.120.42:2181');
$topics = $zookeeper->getchildren("/brokers/topics");
foreach($topics as $topic) {
	echo "Topic: $topic \n";
}

$children = $zookeeper->getchildren("/brokers/ids");
foreach($children as $id) {
	$result = $zookeeper->get("/brokers/ids/$id");
	$arr = json_decode($result, true);
	if(isset($arr['host']) && isset($arr['port'])) {
		echo "ID: $id\t" . $arr['host'] .':'. $arr['port'] . "\n";
	}
	//print_r($arr);
}

$children = $zookeeper->getchildren("/brokers/seqid");
print_r($children);
foreach($children as $id) {
	echo "Looking up seqid: $id\n";
	$result = $zookeeper->get("/brokers/seqid/$id");
	$arr = json_decode($result, true);
	print_r($arr);
}

$brokers = $zookeeper->getchildren("/brokers");
print_r($brokers);

