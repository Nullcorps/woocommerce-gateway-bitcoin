# WordPress 6.1 image with GMP enabled.
# docker build -t wordpress61-gmp -f wordpress61-gmp.Dockerfile .
FROM wordpress:6.1
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gmp