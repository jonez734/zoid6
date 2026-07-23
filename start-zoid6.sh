#!/bin/sh
exec bed --config /etc/zoid6/bed.json --router zoid6.api.handler.MessageRouter "$@"
