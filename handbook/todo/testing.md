# zoid6 TODO: Testing

Owner: zoid6 and bed
Open: 24   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


### bed Daemon Tests
- [ ] Test that `--router` argument loads correct router class
- [ ] Test router registration with WebSocket server
- [ ] Test service message handling
- [ ] Test signal handling (SIGTERM, SIGHUP)

### Module Handler Tests (per module)
- [ ] Test MessageRouter initialization
- [ ] Test service registration
- [ ] Test message handling for each service
- [ ] Test session management

### zoid6 Unified Router Tests
- [ ] Test all module routers are initialized
- [ ] Test all services are registered with server
- [ ] Test cross-module message routing
- [ ] Test full integration with all modules together

### Integration Tests
- [ ] Test running `bed --router zoid6.api.handler.MessageRouter`
- [ ] Test WebSocket connection and authentication
- [ ] Test each module's services respond correctly
- [ ] Test channel pub/sub across modules
- [ ] Test bank transfers between casino and member accounts

### Test Fixtures & Cleanup
- [ ] Create shared fixtures for WebSocket server setup/teardown
- [ ] Create database test pools for each module
- [ ] Mock bbsengine6 services appropriately
- [ ] Ensure ALL tests properly close WebSocket connections
- [ ] Add test fixtures that guarantee cleanup
- [ ] Add assertions that verify server is stopped
- [ ] Check for resource leaks in test teardown

---
