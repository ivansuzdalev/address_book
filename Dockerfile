# src: build/backend/Dockerfile

# Используем за основу контейнера Ubuntu 18.04 LTS
FROM ubuntu:18.04
# Переключаем Ubuntu в неинтерактивный режим — чтобы избежать лишних запросов
ENV DEBIAN_FRONTEND noninteractive 

# Добавляем необходимые репозитарии и устанавливаем пакеты
RUN apt-get update
RUN apt -y install software-properties-common
RUN add-apt-repository -y ppa:ondrej/php
RUN add-apt-repository -y ppa:nginx/stable
RUN apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C
RUN apt-get update
RUN apt-get upgrade -y
RUN apt-get install -y wget curl php7.4-fpm php7.4-mysql php7.4-gd php7.4-curl php-pear php-apcu php7.4-mcrypt php7.4-imagick php7.4-memcache supervisor apache2
RUN apt-get install mc  -y
RUN apt-get install git -y
RUN apt-get install php-sqlite3 -y

# Добавляем описание виртуального хоста
ADD ports.conf /etc/apache2/ports.conf
ADD 000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Объявляем, какой порт этот контейнер будет транслировать
EXPOSE 8000 

# Запускаем supervisor
RUN echo user=root >>  /etc/supervisor/supervisord.conf
CMD ["/usr/bin/supervisord","-n"]

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony
WORKDIR /var/www/html

CMD ["/usr/local/bin/symfony","server:start"]


