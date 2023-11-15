FROM composer

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
