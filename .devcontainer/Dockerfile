FROM php:8.3-fpm

# Configurar variables de entorno necesarias
ENV ACCEPT_EULA=Y
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias básicas
RUN apt-get update && apt-get install -y \
    gnupg2 \
    apt-transport-https \
    software-properties-common \
    curl \
    zip \
    unzip \
    git \
    lsb-release \
    ca-certificates \
    unixodbc \
    unixodbc-dev \
    odbcinst \
    libgssapi-krb5-2 \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libsqlite3-dev \
    libxml2-dev

# Instalar GitHub Client
RUN (type -p wget >/dev/null || (apt update && apt-get install wget -y)) \
	&& mkdir -p -m 755 /etc/apt/keyrings \
    && out=$(mktemp) && wget -nv -O$out https://cli.github.com/packages/githubcli-archive-keyring.gpg \
    && cat $out | tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
	&& chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
	&& echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
	&& apt update \
	&& apt install gh -y

# Agregar la clave de Microsoft y el repositorio correcto para Debian 11
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft-archive-keyring.gpg
RUN echo "deb [arch=amd64,arm64, signed-by=/usr/share/keyrings/microsoft-archive-keyring.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" | tee /etc/apt/sources.list.d/mssql-release.list

# Actualizar y descargar los paquetes de SQL Server
RUN apt-get update && apt-get install -y \
    msodbcsql17 \
    mssql-tools

# Agregar mssql-tools al PATH
RUN echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> /etc/profile

# Instalar extensiones SQL Server manualmente
RUN pecl install sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Crear el usuario "developer"
RUN useradd -m -d /home/developer -s /bin/bash developer

# Dar permisos de escritura al usuario "developer" en el directorio de trabajo
RUN chown -R developer:developer /var/www/html

# Cambiar al usuario "developer"
USER developer

# Agregar mssql-tools al PATH
RUN echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc

# Establecer directorio de trabajo
WORKDIR /var/www/html
