package install

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"runtime"
	"strings"
)

const githubCaddyAPI = "https://api.github.com/repos/caddyserver/caddy/releases/latest"
const postgresAPI = "https://api.github.com/repos/theseus-rs/postgresql-binaries/releases/latest"
const staticPHPBaseUrl = "https://dl.static-php.dev/static-php-cli/bulk/"
const githubMySQLApi = "https://api.github.com/repos/theseus-rs/mysql-binaries/releases/latest"

const MySQLVersion = "8.4.5"

type githubRelease struct {
	TagName string        `json:"tag_name"`
	Assets  []githubAsset `json:"assets"`
}

type githubAsset struct {
	Name               string `json:"name"`
	BrowserDownloadURL string `json:"browser_download_url"`
}

type postgresRelease struct {
	TagName string        `json:"tag_name"`
	Assets  []githubAsset `json:"assets"`
}

func FetchCaddyLatest() (string, string, error) {
	client := &http.Client{
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse
		},
	}
	resp, err := client.Get("https://github.com/caddyserver/caddy/releases/latest")
	if err != nil {
		return "", "", fmt.Errorf("failed to reach GitHub: %w", err)
	}
	resp.Body.Close()

	location := resp.Header.Get("Location")
	if location == "" {
		return "", "", fmt.Errorf("no redirect from GitHub releases page")
	}

	// location: https://github.com/caddyserver/caddy/releases/tag/v2.9.1
	parts := strings.Split(location, "/")
	tag := parts[len(parts)-1]
	version := strings.TrimPrefix(tag, "v")

	url, err := buildCaddyURL(version)
	if err != nil {
		return "", "", err
	}

	return version, url, nil
}

func buildCaddyURL(version string) (string, error) {
	os_ := runtime.GOOS
	arch := runtime.GOARCH

	osMap := map[string]string{
		"darwin":  "mac",
		"linux":   "linux",
		"windows": "windows",
	}
	o, ok := osMap[os_]
	if !ok {
		return "", fmt.Errorf("unsupported OS: %s", os_)
	}

	archMap := map[string]string{
		"amd64": "amd64",
		"arm64": "arm64",
		"386":   "386",
		"arm":   "armv6",
	}
	a, ok := archMap[arch]
	if !ok {
		return "", fmt.Errorf("unsupported arch: %s", arch)
	}

	ext := "tar.gz"
	if os_ == "windows" {
		ext = "zip"
	}

	filename := fmt.Sprintf("caddy_%s_%s_%s.%s", version, o, a, ext)
	return fmt.Sprintf("https://github.com/caddyserver/caddy/releases/download/v%s/%s", version, filename), nil
}

func FetchPostgreSQLLatest() (string, string, error) {
	req, _ := http.NewRequest("GET", postgresAPI, nil)
	req.Header.Set("User-Agent", "luna-dev-manager")
	req.Header.Set("Accept", "application/vnd.github+json")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return "", "", fmt.Errorf("failed to reach GitHub API: %w", err)
	}
	defer resp.Body.Close()

	var release postgresRelease
	if err := json.NewDecoder(resp.Body).Decode(&release); err != nil {
		return "", "", fmt.Errorf("failed to parse response: %w", err)
	}

	version := strings.TrimPrefix(release.TagName, "v")
	url, err := findPostgresAsset(release.Assets, version)
	if err != nil {
		return "", "", err
	}

	return version, url, nil
}

func findPostgresAsset(assets []githubAsset, version string) (string, error) {
	tripleMap := map[string]map[string]string{
		"darwin": {
			"amd64": "x86_64-apple-darwin",
			"arm64": "aarch64-apple-darwin",
		},
		"linux": {
			"amd64": "x86_64-unknown-linux-gnu",
			"arm64": "aarch64-unknown-linux-gnu",
		},
		"windows": {
			"amd64": "x86_64-pc-windows-msvc",
			"arm64": "aarch64-pc-windows-msvc",
		},
	}

	os_ := runtime.GOOS
	arch := runtime.GOARCH

	osTriples, ok := tripleMap[os_]
	if !ok {
		return "", fmt.Errorf("unsupported OS: %s", os_)
	}
	triple, ok := osTriples[arch]
	if !ok {
		return "", fmt.Errorf("unsupported arch: %s", arch)
	}

	ext := "tar.gz"
	if os_ == "windows" {
		ext = "zip"
	}

	target := fmt.Sprintf("postgresql-%s-%s.%s", version, triple, ext)

	for _, asset := range assets {
		if asset.Name == target {
			return asset.BrowserDownloadURL, nil
		}
	}

	return "", fmt.Errorf("no PostgreSQL binary found for %s/%s\nlooking for: %s", os_, arch, target)
}

func FetchPHPLatest() (string, string, error) {
	// static-php.dev tidak punya API, kita fetch index halaman
	// dan cari versi terbaru php-fpm untuk platform ini
	target, err := buildPHPURL()
	if err != nil {
		return "", "", err
	}
	return "latest", target, nil
}

func buildPHPURL() (string, error) {
	os_ := runtime.GOOS
	arch := runtime.GOARCH

	// OS name map
	osMap := map[string]string{
		"darwin":  "macos",
		"linux":   "linux",
		"windows": "windows",
	}
	osName, ok := osMap[os_]
	if !ok {
		return "", fmt.Errorf("unsupported OS: %s", os_)
	}

	// Arch map
	archMap := map[string]string{
		"amd64": "x86_64",
		"arm64": "aarch64",
	}
	archName, ok := archMap[arch]
	if !ok {
		return "", fmt.Errorf("unsupported arch: %s", arch)
	}

	resp, err := http.Get(staticPHPBaseUrl)
	if err != nil {
		return "", fmt.Errorf("failed to fetch PHP index: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", fmt.Errorf("failed to read PHP index: %w", err)
	}
	content := string(body)

	ext := "tar.gz"
	if os_ == "windows" {
		ext = "zip"
		archName = "x64"
	}

	suffix := fmt.Sprintf("-fpm-%s-%s.%s", osName, archName, ext)

	latest := ""
	lines := strings.Split(content, "\n")
	for _, line := range lines {
		if strings.Contains(line, suffix) && strings.Contains(line, "php-8.4.") {
			start := strings.Index(line, "php-8.4.")
			if start < 0 {
				continue
			}
			end := strings.Index(line[start:], suffix)
			if end < 0 {
				continue
			}
			filename := line[start : start+end+len(suffix)]
			if filename > latest {
				latest = filename
			}
		}
	}

	if latest == "" {
		latest = fmt.Sprintf("php-8.4.0-fpm-%s-%s.%s", osName, archName, ext)
	}

	return staticPHPBaseUrl + latest, nil
}

func FetchPHPCLILatest() (string, string, error) {
	url, err := buildPHPCLIURL()
	if err != nil {
		return "", "", err
	}
	return "latest", url, nil
}

func buildPHPCLIURL() (string, error) {
	os_ := runtime.GOOS
	arch := runtime.GOARCH

	osMap := map[string]string{
		"darwin":  "macos",
		"linux":   "linux",
		"windows": "windows",
	}
	osName, ok := osMap[os_]
	if !ok {
		return "", fmt.Errorf("unsupported OS: %s", os_)
	}

	archMap := map[string]string{
		"amd64": "x86_64",
		"arm64": "aarch64",
	}
	archName, ok := archMap[arch]
	if !ok {
		return "", fmt.Errorf("unsupported arch: %s", arch)
	}

	ext := "tar.gz"
	if os_ == "windows" {
		ext = "zip"
		archName = "x64"
	}

	// Fetch index untuk cari versi terbaru
	resp, err := http.Get(staticPHPBaseUrl)
	if err != nil {
		return "", fmt.Errorf("failed to fetch PHP index: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", fmt.Errorf("failed to read PHP CLI index: %w", err)
	}
	content := string(body)

	suffix := fmt.Sprintf("-cli-%s-%s.%s", osName, archName, ext)

	latest := ""
	for _, line := range strings.Split(content, "\n") {
		if strings.Contains(line, suffix) && strings.Contains(line, "php-8.4.") {
			start := strings.Index(line, "php-8.4.")
			if start < 0 {
				continue
			}
			end := strings.Index(line[start:], suffix)
			if end < 0 {
				continue
			}
			filename := line[start : start+end+len(suffix)]
			if filename > latest {
				latest = filename
			}
		}
	}

	if latest == "" {
		latest = fmt.Sprintf("php-8.4.0-cli-%s-%s.%s", osName, archName, ext)
	}

	return staticPHPBaseUrl + latest, nil
}

func MySQLDownloadUrl() (string, error) {
	switch runtime.GOOS {
	case "darwin":
		return "", fmt.Errorf("MySQL on macOS requires Homebrew: brew install mysql")
	case "windows":
		arch := "winx64"
		if runtime.GOARCH == "arm64" {
			arch = "winx64"
		}
		return fmt.Sprintf(
			"https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-%s-%s.zip",
			MySQLVersion, arch,
		), nil
	default:
		arch := "linux-glibc2.28-x86_64"
		if runtime.GOARCH == "arm64" {
			arch = "linux-glibc2.28-aarch64"
		}
		return fmt.Sprintf(
			"https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-%s-%s.tar.xz",
			MySQLVersion, arch,
		), nil
	}
}
