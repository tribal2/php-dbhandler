ARG PHP_VERSION=8.2
ARG ALPINE_VERSION=3.18
ARG COMPOSER_VERSION=latest

FROM composer:${COMPOSER_VERSION} AS composer

FROM php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION}

# Copiamos Composer PHAR desde la imagen de composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN set -ex \
    && apk --update add --no-cache --virtual .php-ext-install-deps \
        $PHPIZE_DEPS \
        autoconf \
    && docker-php-ext-install -j$(nproc) \
    && runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )" \
    && apk add --no-cache $runDeps \
    && docker-php-source delete \
    && apk del --no-network .php-ext-install-deps \
    # Set America/Panama timezone
    && apk --update add --no-cache tzdata \
    && cp /usr/share/zoneinfo/America/Panama /etc/localtime \
    && echo "America/Panama" > /etc/timezone \
    && apk del tzdata

#***********#
# OS SETUP  #
#***********#

ARG ENVIRONMENT=development
RUN set -ex \
    # Tools
    && apk --update add --no-cache \
        linux-headers \
        openssh \
        git \
        bash \
    # XDebug
    && apk --update add --no-cache --virtual .php-ext-install-deps \
        $PHPIZE_DEPS \
        autoconf \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del --no-network .php-ext-install-deps \
    && composer self-update

#****************#
#   USER SETUP   #
#****************#
RUN addgroup -S devcontainer \
    && adduser -S devcontainer -G devcontainer -h /home/devcontainer
COPY ./.devcontainer/.cfg/nix/* /home/devcontainer/

#*****************#
# DBHANDLER SETUP #
#*****************#

WORKDIR /dbhandler

COPY ./composer.* /dbhandler/

# User permissions
RUN chown -R devcontainer:devcontainer \
        /dbhandler \
        /home/devcontainer \
    # Fix bug in ikappas/vscode-phpcs with squizlabs/php_codesniffer when XDebug is
    # enabled. For more info, see:
    # https://github.com/ikappas/vscode-phpcs/issues/189#issue-815494036
    # https://github.com/digitoimistodude/macos-lemp-setup#xdebug
    && mv /home/devcontainer/FIX-phpcs /usr/local/bin/phpcs \
    && chmod +x /usr/local/bin/phpcs

USER devcontainer

# PHP dev packages
RUN cd /dbhandler \
    && composer update \
    && composer install \
    # PHP Global dev packages
    && composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true \
    && composer global require squizlabs/php_codesniffer \
    && composer global require dealerdirect/phpcodesniffer-composer-installer \
    && composer global require sirbrillig/phpcs-variable-analysis
