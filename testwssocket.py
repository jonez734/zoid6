import asyncio
import json
import websockets
async def main() -> None:
    async with websockets.connect("ws://127.0.0.1:8765/") as ws:
        await ws.send(json.dumps({"type": "list_services", "request_id": "probe-ls"}))
        raw_ls = await asyncio.wait_for(ws.recv(), timeout=2.0)
        print("list_services reply:")
        print(json.dumps(json.loads(raw_ls), indent=2))
        await ws.send(
            json.dumps(
                {
                    "type": "auth",
                    "moniker": "nobody-probe",
                    "password": "",
                    "request_id": "probe-auth",
                }
            )
        )
        raw_auth = await asyncio.wait_for(ws.recv(), timeout=2.0)
        print("auth reply:")
        print(json.dumps(json.loads(raw_auth), indent=2))
asyncio.run(main())
