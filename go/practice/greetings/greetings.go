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

// Hellos returns a map that associates each of the named people with a greeting.
func Hellos(names []string) (map[string]string, error) {
	// A map to associate names with greetings.
	messages := make(map[string]string)

	// Loop through the received slice of names, calling the Hello function to get a greeting for each name.
	for _, name := range names {
		message, err := Hello(name)

		if err == nil {
			messages[name] = message
		} else { // In the map, associate the retrieved greeting with the name
			return nil, err
		}
	}

	return messages, nil
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
