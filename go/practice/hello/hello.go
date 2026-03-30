package main

import (
	"fmt"
	"log"
	"practice/greetings"
)

func main() {
	// Set properties of the predefined Logger, including
	// the log entry prefix and a flag to disable printing
	// the time, source file, and line number.
	log.SetPrefix("greetings: ")
	log.SetFlags(0)

	greeting, err := greetings.Hello("Gladys")

	if err == nil { // If no error was returned, print the returned message to the console.
		fmt.Println(greeting)
	} else { // If an error was returned, print it to the console and exit the program.
		log.Fatal(err)
	}
}
