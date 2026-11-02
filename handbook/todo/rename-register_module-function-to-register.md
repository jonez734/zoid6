# rename register_module to register — zoid6

bbsengine6 now provides `register(name, module_path, version, apis)` and `unregister(name)`.
`register_module` and `unregister_module` remain as thin aliases. zoid6 does not call them directly
today, so this is a check, not a required change.

## Open

- [ ] Search zoid6 (`src/`, `scripts/`) for `register_module` and `unregister_module`, including any
      message or log text that names them. Migrate any call sites found.
- [ ] Confirm zoid6's `services` loader does not depend on the old names through the bbsengine6 module
      registry.
