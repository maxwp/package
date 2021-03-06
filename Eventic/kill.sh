#!/usr/bin/bash

kill $(ps aux | grep 'ee-worker' | awk '{print $2}')

rm pid/*.pid