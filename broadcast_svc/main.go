// broadcast_svc project main.go
package main

import (
	"log"
	"net/http"
	"strings"
)

var B *Broadcast

func main() {
	B = NewBroadcast()

	http.HandleFunc("/sub", onSub)
	http.HandleFunc("/pub", onPub)
	err := http.ListenAndServe(":8421", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}

func onSub(w http.ResponseWriter, req *http.Request) {
	l := B.Sub()
	defer l.Close()
	cn := w.(http.CloseNotifier)

	log.Println("Client", req.RemoteAddr, "sub the channel")

	for {
		select {
		case msg := <-l.Read():
			w.Header().Set("Access-Control-Allow-Origin", "*") //跨域问题
			w.Write([]byte(msg.(string)))
			log.Println("Msg sent to client", req.RemoteAddr)
			return
		case <-cn.CloseNotify():
			log.Println("Client", req.RemoteAddr, "disconnected")
			return
		}
	}
}

func onPub(w http.ResponseWriter, req *http.Request) {

	if !strings.HasPrefix(req.RemoteAddr, "127.0.0.1") {
		w.Write([]byte("Broadcast from localhost ONLY! You are" + req.RemoteAddr))
		return
	}

	req.ParseForm()
	news := req.Form.Get("line")
	B.Pub(news)
	log.Println("Msg sent to channel:", news)
}
