# zoid6 TODO: zoid6.tui and zoid6.prod deploy split

Owner: zoid6 and deploytool
Open: 2   Done: 4

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


### Problem

`zoid6/src/Makefile` already separates the non-sudo `deploy-tui`
target (an alias for `install`, which is `pip install -e .`
against the active venv) from the sudo umbrella `install-fhs`
(sysusers + tmpfiles + per-service venv + systemd + /etc/zoid6).
But deploytool's `TARGETS` (`deploytool/src/deploytool/lib.py:97`)
only registered `["www", "tui"]` — no `prod` sub-target — so
`deploy zoid6.prod` was unreachable through deploytool, and
operators had to run `make -C zoid6 install-fhs` by hand.

The mirror split exists for bed (`bed/Makefile:204-221`):
`deploy-venv` (non-sudo) + `deploy-prod: install` (sudo). This
section brings zoid6 to the same shape so deploytool users have
a single, predictable entry point.

### Goal

Two named deploytool sub-targets, both routed through `make`:

- `zoid6.tui` (default, non-sudo) — `make -C zoid6/src
  deploy-tui` → `install` → `pip install -e .` against the
  active venv. No sudo.
- `zoid6.prod` (explicit, sudo) — `make -C zoid6/src
  deploy-prod` → `install-fhs` (full prod umbrella). Sudo.

`deploy zoid6` (no sub) auto-expands to `www + tui + prod`,
but `prod` is dropped at
`deploytool/src/deploytool/lib.py:296` unless explicit, so the
default path is non-sudo. `deploy zoid6.prod` is explicit.

### Tasks

- [x] Add `zoid6/src/Makefile:155-160` `deploy-prod: install-fhs`
      target. Add to `.PHONY` (line 55) and `help` block
      (lines 92-93).
- [x] Add `zoid6/Makefile:82-83` `deploy-prod` shim delegating
      to `src/Makefile:deploy-prod` (mirrors the existing
      `install-fhs` shim at line 90). Add to `.PHONY` (line 66).
- [x] Register `"zoid6": ["www", "tui", "prod"]` in
      `deploytool/src/deploytool/lib.py:97` `TARGETS`.
- [x] Verify with `deploytool --dry-run`:
      - `zoid6.tui` → `[..., zoid6.tui]` (no sudo steps).
      - `zoid6.prod` → `[zoid6.prod]` (sudo umbrella steps).
      - `zoid6` → `[..., zoid6.www, zoid6.tui]` (auto-drops
        `prod`).
- [ ] Real-run smoke test: `deploytool deploy zoid6.tui` exits 0
      with no `sudo` prompts.
- [ ] Real-run smoke test: `deploytool deploy zoid6.prod` (with
      sudo) exits 0 and `systemctl status zoid6` shows the
      service loaded.

### Cross-references

- `zoid6/src/Makefile:94-95` — `install` body (non-sudo;
  `deploy-tui: install`).
- `zoid6/src/Makefile:233` — `install-fhs` body that
  `deploy-prod: install-fhs` reuses.
- `zoid6/Makefile:79-83` — top-level `deploy-tui` +
  `deploy-prod` shims.
- `deploytool/src/deploytool/lib.py:97` — `TARGETS["zoid6"]`
  now lists `["www", "tui", "prod"]`.
- `deploytool/src/deploytool/lib.py:294-303` — final pass drops
  auto-expanded `prod` subs unless explicit (same rule that
  governs `bed.prod`).
- `bed/TODO.md` "## `bed.venv` (non-sudo) / `bed.prod` (sudo)
  deploy split" — sibling section; bed's `deploy` target was
  also flipped to non-sudo (`bed/Makefile:223` `deploy:
  deploy-venv`) to match.
