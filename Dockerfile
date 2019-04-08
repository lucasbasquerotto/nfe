FROM composer:1.8.4 AS composer

FROM php:7.3.3-cli-stretch

# copy the Composer PHAR from the Composer image into the PHP image
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1 

RUN apt-get update \
 && apt-get install -y \
	# libmcrypt-dev \
# 	libbz2-dev \
	libpng-dev \
# 	libgmp-dev \
	libxml2-dev \
# 	libxslt-dev \
	libzip-dev \
#  && ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/include/gmp.h \
#  && pecl install mcrypt-1.0.1 \
 && docker-php-ext-install \
    # curl \
    # dom \
    gd \ 
    # json \
    # mbstring \
    # mcrypt \
    # openssl \
    soap \
    # xml \
    zip \
 && mkdir -p /usr/src/myapp \
 && cd /usr/src/myapp \
 && composer require nfephp-org/sped-nfe \
 && mkdir -p /var/main/

WORKDIR /usr/src/myapp
COPY . /usr/src/myapp
CMD [ "php", "./main.php" ]