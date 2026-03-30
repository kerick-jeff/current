package main

import (
	"fmt"
	"practice/greetings"
)

func main() {
	greeting := greetings.Hello("Gladys")
	fmt.Println(greeting)
}
