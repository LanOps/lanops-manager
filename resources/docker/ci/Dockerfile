# Image for the Drone lint and test steps, so they don't install packages and compile
# PHP extensions on every build. It is built and pushed to lanopsdev/lanops-manager:ci-php8.4
# by the "ci-image" pipeline in .drone.yml, which only runs when started by hand
# (Drone "New Build"). Run it again after changing this file.
FROM php:8.4-cli

RUN apt-get update -q \
    && apt-get install -yq --no-install-recommends git unzip libfreetype-dev libjpeg62-turbo-dev libpng-dev libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath exif gd pdo_mysql

# imagick renders the PNG ticket QR codes (same version as the app image)
RUN pecl install imagick-3.8.0 && docker-php-ext-enable imagick

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
