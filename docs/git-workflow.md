# Panduan Git — Push ke Fork & Tag/Release ke Base

Panduan ringkas alur kerja git untuk berkontribusi ke **Luna**, supaya tidak
salah saat push ke fork dan membuat tag/release ke repo organisasi.

---

## 0. Setup remote (acuan)

| Remote | URL | Peran |
|---|---|---|
| `origin` | `https://github.com/adtzslowy/luna.git` | **fork** (punyamu) — tempat kamu push |
| `upstream` | `https://github.com/lunara-sh/luna.git` | **base** (organisasi) — sumber utama |

Cek dengan:
```bash
git remote -v
```

Kalau `upstream` belum ada, tambahkan sekali saja:
```bash
git remote add upstream https://github.com/lunara-sh/luna.git
```

---

## 1. Konsep dasar (ingat ini!)

- Sintaks push selalu: **`git push <remote> <branch-atau-tag>`**.
  - `v0.1.1` itu **tag**, BUKAN nama remote.
  - ❌ `git push v0.1.1` → error "does not appear to be a git repository"
  - ✅ `git push origin v0.1.1`
- Arah Pull Request: **fork → base**
  - head = `adtzslowy/luna` → base = `lunara-sh/luna`

---

## 2. Alur harian (fitur / bugfix)

```bash
# 1. Sinkron main dengan base dulu
git checkout main
git fetch upstream
git merge --ff-only upstream/main

# 2. Buat branch baru (JANGAN kerja langsung di main)
git checkout -b fix/nama-singkat

# 3. Edit kode, lalu commit
git add <file>
git commit -m "fix: ringkasan perubahan"

# 4. Push ke FORK
git push -u origin fix/nama-singkat
```

Lalu buka Pull Request di GitHub:
- **base repository**: `lunara-sh/luna` — **base**: `main`
- **head repository**: `adtzslowy/luna` — **compare**: `fix/nama-singkat`

> Atau pakai CLI:
> ```bash
> gh pr create --repo lunara-sh/luna --base main \
>   --head adtzslowy:fix/nama-singkat \
>   --title "fix: ..." --body "Penjelasan..."
> ```

---

## 3. Kalau push ditolak `! [rejected] ... (fetch first)`

Artinya base sudah maju (ada commit baru yang belum kamu punya). Tarik dulu:

```bash
git fetch upstream
git merge --ff-only upstream/main    # atau: git rebase upstream/main
# baru push lagi
```

---

## 4. Alur tag / release yang BENAR (urutan penting)

Lakukan **setelah** perubahan masuk ke `main` base.

```bash
# 1. Bump versi di kode DULU
#    cmd/runtime.go  ->  const version = "0.1.1"
git add cmd/runtime.go
git commit -m "chore(release): bump version to 0.1.1"

# 2. Buat tag DI commit hasil bump
git tag -a v0.1.1 -m "v0.1.1: ringkasan rilis"

# 3. Push tag (butuh akses write ke base untuk upstream)
git push upstream v0.1.1     # tag ke base
git push origin v0.1.1       # tag ke fork juga (biar konsisten)
```

Terakhir, buat **GitHub Release** dari tag:
```bash
gh release create v0.1.1 --repo lunara-sh/luna \
  --title "v0.1.1" --notes "Catatan rilis..."
```
(atau lewat web: Releases → Draft a new release → pilih tag `v0.1.1`)

---

## 5. Aturan emas soal tag

- **Jangan pernah memindahkan tag yang sudah dipublish.** Kalau salah commit,
  lebih baik rilis versi baru (`v0.1.2`) daripada `--force` tag lama.
- Tag harus menunjuk ke commit yang **versinya sudah benar**. Jangan menge-tag
  commit yang `const version`-nya belum di-bump (nanti `luna version` salah).
- Urutan yang benar: **bump versi → commit → baru tag**. Bukan sebaliknya.

---

## 6. Kesalahan umum & perbaikannya

| Yang salah | Akibat | Yang benar |
|---|---|---|
| `git push v0.1.1` | "does not appear to be a git repository" | `git push origin v0.1.1` |
| `git push upstream main` saat base lebih baru | `rejected (fetch first)` | `git fetch upstream` + `git merge --ff-only upstream/main` dulu |
| Tag dibuat sebelum bump versi | `luna version` cetak versi lama | bump versi → commit → baru tag |
| Force-pindah tag yang sudah publik | rilis jadi membingungkan | buat versi baru saja (`v0.1.2`) |
| Kerja & commit langsung di `main` fork | `main` jadi kotor, susah sinkron | selalu pakai branch (`fix/...`, `feat/...`) |

---

## 7. Perintah cek cepat (aman, read-only)

```bash
git remote -v                      # lihat origin & upstream
git status                         # posisi branch & perubahan
git log --oneline --graph -10      # riwayat commit
git ls-remote --tags upstream      # daftar tag di base
git branch --show-current          # nama branch sekarang
```
