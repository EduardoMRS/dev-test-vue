FROM dunglas/frankenphp:1.3-php8.3-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        gnupg \
        ca-certificates \
        netcat-openbsd \
        postgresql-client \
        libpq-dev \
        npm \
        pkg-config \
        build-essential \
    && rm -rf /var/lib/apt/lists/*

# Build and install PHP extensions
RUN docker-php-ext-install pdo_pgsql bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy entrypoint and make executable
COPY ./docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
