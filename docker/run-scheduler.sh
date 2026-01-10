#!/bin/bash

while true
do
  echo "⏰ Running scheduler at $(date)"
  php artisan schedule:run --no-interaction
  sleep 60
done