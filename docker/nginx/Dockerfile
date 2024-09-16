# Здесь задается базовый образ для  контейнера.
# Используется последняя версия официального образа nginx из Docker Hub.
FROM nginx:latest


# Копирование файла test_task.conf из текущего контекста сборки (т.е. из директории, где находится этот Dockerfile)
# в специальное место внутри нового образа (/etc/nginx/conf.d/default.conf).
# Файл будет использоваться в качестве конфигурации сервера nginx.
COPY test_task.conf /etc/nginx/conf.d/default.conf
