package greetings

import (
	"errors"
	"fmt"
	"math/rand"
)

// Hello returns a greeting for the named person.
func Hello(name string) (string, error) {
	var message string

	if name == "" {
		message = name
		return message, errors.New("empty name")
	} else { // Return a greeting that embeds the name in a message.
		message = fmt.Sprintf(randomFormat(), name)
		return message, nil
	}
}

// Returns one of a set of greeting messages. The returned message is selected at random.
func randomFormat() string {
	// A slice of message formats.
	formats := []string{
		"Hi, %v. Welcome!",
		"Great to see you, %v!",
		"Hail, %v! Well met!",
	}

	// Return a randomly selected message format by specifying a random index for the slice of formats.
	return formats[rand.Intn(len(formats))]
}
