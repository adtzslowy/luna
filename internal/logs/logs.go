package logs

import (
	"bufio"
	"fmt"
	"io"
	"os"
	"time"
)

func Tail(path string, follow bool, lines int) error {
	f, err := os.Open(path)
	if err != nil {
		return fmt.Errorf("cannot open log file: %w", err)
	}
	defer f.Close()

	lastLines, err := tailLines(f, lines)
	if err != nil {
		return err
	}

	for _, line := range lastLines {
		fmt.Println(line)
	}

	if !follow {
		return nil
	}

	f.Seek(0, io.SeekEnd)
	fmt.Printf("\n--- following %s (CTRL + C to stop) ---\n\n", path)

	for {
		scanner := bufio.NewScanner(f)
		for scanner.Scan() {
			fmt.Println(scanner.Text())
		}

		time.Sleep(500 * time.Millisecond)
	}
}

func tailLines(f *os.File, n int) ([]string, error) {
	var lines []string
	scanner := bufio.NewScanner(f)
	for scanner.Scan() {
		lines = append(lines, scanner.Text())
		if len(lines) > n {
			lines = lines[1:]
		}
	}
	return lines, scanner.Err()
}
