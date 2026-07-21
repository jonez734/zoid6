#!/bin/sh
exec bed --config "$(dirname "$0")/src/zoid6/data/bed.json" --router zoid6.api.handler.MessageRouter "$@"
