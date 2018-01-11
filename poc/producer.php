<?php

if(!class_exists("RdKafka\Conf")) {
	die("RdKafka PECL module not installed");
}

if(!class_exists("Zookeeper")) {
	die("Zookeeper PECL module not installed");
}

$zk_ep = getenv("ZK_EP");
if($zk_ep === false) {
	// Assume Uranus for testing
	$zk_ep = '10.115.120.42:2181';
}

// Discover topics.
$zookeeper = new Zookeeper($zk_ep);
$topic = "test";
$topics = $zookeeper->getchildren("/brokers/topics");
foreach($topics as $t) {
	echo "Topic: $t \n";
}

// Discover brokers.
$brokers = [];
$broker_list = [];
$children = $zookeeper->getchildren("/brokers/ids");
foreach($children as $key => $id) {
	$result = $zookeeper->get("/brokers/ids/$id");
	echo "JSON: $result\n";
	$arr = json_decode($result, true);
	if(isset($arr['host']) && isset($arr['port'])) {
		$brokers[] = [ 'id' => $id, 'ep' => $arr['host'].':'.$arr['port'] ];
		$broker_list[] = $arr['host'].':'.$arr['port'];
	}
}

if(count($brokers) == 0) {
	die("Failed to discover any Kafka brokers\n");
}

$rk = new RdKafka\Producer();
$list = implode(",", $broker_list);
echo "Adding broker list: $list\n";
$rk->addBrokers($list);
$topic = $rk->newTopic($topic);

function process(RdKafka\Producer $in_prod, RdKafka\Topic $in_topic, $in_msg) {
	$hostname = gethostname();
	echo "$hostname sending msg: \"$in_msg\"\n";
	$in_topic->produce(RD_KAFKA_PARTITION_UA, 0, $in_msg);
	$in_prod->poll(0);	
	while($in_prod->getOutQLen() > 0) {
		$in_prod->poll(50);
	}
}

$loop = 0;

while(true) {
	$msg = "Message number $loop";
	process($rk, $topic, $msg);
	sleep(1);
	$loop++;	
}






