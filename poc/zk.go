package main

import (
	"fmt"
	"time"
	"bytes"
	"encoding/json"
	"github.com/samuel/go-zookeeper/zk"
)

func main() {

	// Begin by asking Zookeeper for the Kafka brokers
	var brokers []string

	c, _, err := zk.Connect([]string{"10.115.120.42"}, time.Second*10)
	if err != nil {
		panic(err)
	}
	children, stat, _, err := c.ChildrenW("/brokers/ids")
	if err != nil {
		panic(err)
	}
	if stat == nil {
		panic(err)
	}
	fmt.Printf("children: %+v\n", children)
	//fmt.Printf("stat: %+v\n", stat)

	var brokers_ep bytes.Buffer

	for i := 0; i < len(children); i++ {
		var str string
		var buffer bytes.Buffer
		buffer.WriteString("/brokers/ids/")
		buffer.WriteString(children[i])
		str = buffer.String()
		fmt.Printf("%+v %s %s\n", i, children[i], str)
		broker, _, err := c.Get(str)
		if err == nil {
			var broker_map map[string]interface{}
			err = json.Unmarshal(broker, &broker_map)
			if err == nil {
				ep := fmt.Sprintf("%s:%v", broker_map["host"], broker_map["port"])
				brokers = append(brokers, ep)
				fmt.Printf("    endpoint: %s\n", ep)
				if i > 0 {
					brokers_ep.WriteString(",")
				}
				brokers_ep.WriteString(ep)
			}
		}
	}	

	fmt.Printf("brokers_ep = %s\n\n", brokers_ep.String())

	for i := 0; i < len(children); i++ {
		str := fmt.Sprintf("/brokers/ids/%v", children[i])
		fmt.Printf("%s\n", str)
		broker, _, err := c.Get(str)
		if err == nil {
			var broker_map map[string]interface{}
			err = json.Unmarshal(broker, &broker_map)
			if err == nil {
				ep := fmt.Sprintf("%s:%v", broker_map["host"], broker_map["port"])
				brokers = append(brokers, ep)
				fmt.Printf("    endpoint: %s\n", ep)
				if i > 0 {
					brokers_ep.WriteString(",")
				}
				brokers_ep.WriteString(ep)
			}
		}
	}
}

