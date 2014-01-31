// broadcast
package main

import (
	"sync"
)

type Broadcast struct { //各种地方不知道用指针还是怎么样
	writeBuffer chan interface{}
	listener    []*Receiver
	lock        *sync.Mutex
}

type Receiver struct {
	C    chan interface{}
	Free bool
}

func NewBroadcast() (b *Broadcast) {
	writeBuffer := make(chan interface{})
	listener := []*Receiver{}

	go func() { //分发过程
		for {
			news := <-b.writeBuffer
			for k, _ := range b.listener {
				if b.listener[k].Free { //已关闭的不投递消息
					continue
				}
				b.listener[k].C <- news
			}
		}

	}()
	return &Broadcast{
		writeBuffer: writeBuffer,
		listener:    listener,
		lock:        new(sync.Mutex),
	}
}

func (b *Broadcast) Pub(news interface{}) {
	go func() {
		b.writeBuffer <- news
	}()
}

func (b *Broadcast) Sub() (listener *Receiver) {
	b.lock.Lock()
	defer b.lock.Unlock()
	for k, _ := range b.listener {
		if b.listener[k].Free {
			b.listener[k].Free = false
			return b.listener[k]
		}
	}
	listener = &Receiver{C: make(chan interface{})}
	b.listener = append(b.listener, listener)
	return listener
}

func (r Receiver) Read() <-chan interface{} {
	return r.C
}

func (r *Receiver) Close() {
	r.Free = true
}
