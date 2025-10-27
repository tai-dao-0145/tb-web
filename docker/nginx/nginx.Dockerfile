FROM nginx

LABEL maintainer="tb-developer"
# Install modules
RUN apt-get update && apt-get install -y \
  gettext-base \
  g++ \
  zip \
  vim \
  curl \
  openssl \
  libssl-dev \
  --no-install-recommends apt-utils \
  && rm -r /var/lib/apt/lists/*

WORKDIR /work/${COMPOSE_PROJECT_NAME}
