//go:build windows

package process

import (
	"os"
	"syscall"
)

var (
	modkernel32          = syscall.NewLazyDLL("kernel32.dll")
	procOpenProcess      = modkernel32.NewProc("OpenProcess")
	procWaitForSingleObject = modkernel32.NewProc("WaitForSingleObject")
	procCloseHandle      = modkernel32.NewProc("CloseHandle")
)

const (
	processQueryLimitedInformation = 0x1000
	waitObject0                   = 0x00000000
	waitTimeout                   = 0x00000102
)

func isAlive(pid int) bool {
	if pid <= 0 {
		return false
	}

	handle, _, _ := procOpenProcess.Call(
		processQueryLimitedInformation,
		0,
		uintptr(pid),
	)
	if handle == 0 {
		return false
	}
	defer procCloseHandle.Call(handle)

	ret, _, _ := procWaitForSingleObject.Call(handle, 0)
	// WAIT_TIMEOUT (0x102) means process is still running.
	// WAIT_OBJECT_0 (0x0) means process has exited.
	return ret == waitTimeout
}

func terminateProcess(proc *os.Process) error {
	return proc.Kill()
}
