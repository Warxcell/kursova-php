#!/bin/bash

docker build . -t kursova_php --target app_prod


docker run --env-file ./.env.local -p 8080:8080 kursova_php