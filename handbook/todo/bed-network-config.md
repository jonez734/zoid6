# zoid6 TODO: Bed network configuration

Owner: bed
Open: 8   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

### Bed Network Configuration

- [ ] Add `bind` option to bed.json config file
- [ ] Add `--bind` option to bed daemon CLI with support for:
  - `localhost` - resolves to all IPs for localhost (via DNS), binds to each
  - `127.0.0.1` - IPv4 localhost only
  - `::1` - IPv6 localhost only
  - `0.0.0.0` - all IPv4 interfaces
  - `::` - all IPv6 interfaces
  - Specific IP addresses
- [ ] Implement dynamic resolution:
  - When `localhost` is specified, resolve via socket.getaddrinfo() to get all IPs
  - Bind to each returned IP
  - User is responsible for proper DNS configuration (no filtering of results)
- [ ] Implement dual-stack binding:
  - For each resolved IP, create a WebSocket server socket
  - All sockets share the same router/services
  - Handle connection distribution across all sockets
- [ ] Default to `localhost` for secure by default (binds to both IPv4/IPv6 localhost)
- [ ] Add validation for bind address format
- [ ] Handle "address already in use" errors gracefully
- [ ] Ensure client can connect to localhost regardless of whether it resolves to IPv4 or IPv6

---
