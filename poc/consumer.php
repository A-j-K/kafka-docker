<?php

if(! class_exists("RdKafka\Conf")) {
	die("RdKafka PECL module not installed");
}

if(! class_exists("Zookeeper")) {
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
	$arr = json_decode($result, true);
	if(isset($arr['host']) && isset($arr['port'])) {
		$brokers[] = [ 'id' => $id, 'ep' => $arr['host'].':'.$arr['port'] ];
		$broker_list[] = $arr['host'].':'.$arr['port'];
	}
}

if(count($brokers) == 0) {
	die("Failed to discover any Kafka brokers\n");
}

$conf = new RdKafka\Conf();
$conf->set('group.id', 'myConsumerGroup');

$rk = new RdKafka\Consumer($conf);

$list = implode(",", $broker_list);
echo "Adding broker list: $list\n";
$rk->addBrokers($list);
$rk->setLogLevel(LOG_DEBUG);

$topicConf = new RdKafka\TopicConf();
$topicConf->set('auto.commit.interval.ms', 100);
$topicConf->set('offset.store.method', 'file');
$topicConf->set('offset.store.path', sys_get_temp_dir());
$topicConf->set('auto.offset.reset', 'smallest');

$topic = $rk->newTopic($topic, $topicConf);

//$topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
$topic->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);

$hostname = gethostname();

function process(RdKafka\Topic $in_topic) {
	$message = $in_topic->consume(0, 120*1000);
	if(is_null($message)) {
		echo "Null says timeout, retrying\n";
	}
	else if($message instanceof RdKafka\Message) {
		switch ($message->err) {
		case RD_KAFKA_RESP_ERR_NO_ERROR:
			echo "$hostname received message: " . $message->payload . "\n";
			//var_dump($message);
			break;
		case RD_KAFKA_RESP_ERR__PARTITION_EOF:
			echo "No more messages\n";
			break;
		case RD_KAFKA_RESP_ERR__TIMED_OUT:
			echo "Timed out\n";
			break;
		default:
			throw new \Exception($message->errstr(), $message->err);
			break;
		}
	}
	else {
		echo "This should never happen\n";
	}
}

while(true) {
	try {
		process($topic);
	}
	catch(RdKafka\Exception $e) {
		echo "Exception: " . $e->get_message() . "\n";
	}
}






