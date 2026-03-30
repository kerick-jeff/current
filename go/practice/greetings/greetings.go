package greetings

import (
	"errors"
	"fmt"
)

// Hello returns a greeting for the named person.
func Hello(name string) (string, error) {
	var message string

	if name == "" {
		message = name
		return message, errors.New("empty name")
	} else { // Return a greeting that embeds the name in a message.
		message = fmt.Sprintf("Hi, %v! Welcome.", name)
		return message, nil
	}
}
