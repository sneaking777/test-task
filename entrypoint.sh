cd docker || exit

docker-compose down
docker-compose rm -f
docker-compose build --no-cache
docker-compose up -d