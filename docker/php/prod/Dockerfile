FROM php:8.3-fpm-alpine

SHELL ["/bin/ash", "-oeux", "pipefail", "-c"]

# timezone
ARG TZ

RUN apk add --no-cache linux-headers

RUN apk update && \
  apk add --update --no-cache --virtual=.build-dependencies \
  autoconf \
  gcc \
  g++ \
  make \
  tzdata \
  git && \
  apk add libpng libpng-dev libjpeg-turbo-dev libwebp-dev zlib-dev libxpm-dev gd supervisor && \
  apk add --update --no-cache \
  icu-dev \
  libzip-dev \
  oniguruma-dev && \
  cp /usr/share/zoneinfo/${TZ} /etc/localtime && \
  echo ${TZ} > /etc/timezone && \
  apk del .build-dependencies && \
  docker-php-ext-install intl pdo_mysql mbstring zip bcmath gd sockets

# cron
RUN apk --update add --no-cache dcron

COPY cron.txt /etc/crontabs/root
RUN chmod 0644 /etc/crontabs/root
RUN crontab /etc/crontabs/root

RUN mkdir -p /var/log/cron
RUN touch /var/log/cron/app.log

# service
COPY startup.sh /root/startup.sh
RUN chmod +x /root/startup.sh

# composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
