FROM php:7.3-alpine
RUN docker-php-ext-install mysqli pdo pdo_mysql
