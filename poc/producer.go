package main

import (
	"os"
	"fmt"
	"time"
	"encoding/json"
	"github.com/Shopify/sarama"
	"github.com/samuel/go-zookeeper/zk"
)

func do_zoo(in_zoo []string) []string {
	var broker_list []string

	zoo, _, err := zk.Connect(in_zoo, time.Second*10)
	if err != nil {
		panic(err)
	}
	broker_ids, stat, _, err := zoo.ChildrenW("/brokers/ids")
	if err != nil || stat == nil {
		panic(err)
	}

	for i := 0; i < len(broker_ids); i++ {
		key := fmt.Sprintf("/brokers/ids/%v", broker_ids[i])
		if broker_val, stat, err := zoo.Get(key); err == nil && stat != nil {
			var broker_map map[string] interface{}
			if err = json.Unmarshal(broker_val, &broker_map); err == nil {
				ip_port := fmt.Sprintf("%s:%v", broker_map["host"], broker_map["port"])
				broker_list = append(broker_list, ip_port)
			}
		}
	}	
	return broker_list
}

func do_kafka(in_brokers []string, in_topic string) {
	config := sarama.NewConfig()
	config.Producer.RequiredAcks = sarama.WaitForAll
	config.Producer.Retry.Max = 5
	config.Producer.Return.Successes = true
	producer, err := sarama.NewSyncProducer(in_brokers, config)
	if err != nil {
		panic(err)
	}	
	defer func() {
		if err := producer.Close(); err != nil {
			panic(err)
		}
	}()
	name, _ := os.Hostname()
	for {
		t := time.Now()
		str := fmt.Sprintf("{ \"Hostname\":\"%s\", \"Time\": \"%s\" }", name, t.String())
		val := sarama.StringEncoder(str)
		msg := &sarama.ProducerMessage{
			Topic: in_topic,
			Value: val,
		}
		partition, offset, err := producer.SendMessage(msg)
		if err != nil {
			panic(err)
		}
		fmt.Printf("Message is stored in topic(%s)/partition(%d)/offset(%d)\n", in_topic, partition, offset)
		duration := time.Second
		time.Sleep(duration)
	}
}

func main() {
	var zoos []string
	zoos = append(zoos, "10.115.120.42")
	brokers := do_zoo(zoos)
	if len(brokers) == 0 {
		fmt.Printf("No Kafka brokers found\n")
	} else {
		do_kafka(brokers, "test");
	}
}

