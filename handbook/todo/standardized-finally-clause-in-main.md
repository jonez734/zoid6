# standardized finally clause in main — zoid6

Every program's `finally` on exit should call `bottombar.shutdown()`, and every program's setup
should call `bottombar.init()`. Neither should use the deprecated `screen.init()` shim or a private
once-per-process flag.

Contract (bbsengine6 `bottombar`):
- `init()` runs once per process, and sets the scroll region on a tty only.
- `shutdown()` shuts down object fragments first, then, only if `init()` ran, clears the bottom bar line,
  resets colors and margins (`{reset}`), and restores the cursor. It never raises and does nothing without
  a tty.
- Interrupt and EOF messages print `{/all}*INTR*` / `{/all}*EOF*` with no cursor restore in the text, so
  `shutdown()` restores the cursor after the message.

Verify by driving the program under a pty, interrupting it, and checking that the exit bytes end in this
order: color reset, message, bar-line clear, color reset, margin reset, cursor restore.

## Open

- [ ] Check the zoid6 entry points (`zoid6`, `zoid6d`) for a screen or bottombar setup. zoid6 is a daemon host and sysop CLI; if the sysop CLI draws a bar, give it the same `finally`. Otherwise record that none is needed.
- [ ] If `zoid6d` draws no screen, add nothing and mark this item done with that reason.
