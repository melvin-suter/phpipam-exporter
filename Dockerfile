FROM phpipam/phpipam-www:1.6x

ENV EXPORT_FROM="export@example.com"
ENV EXPORT_TO="export@example.com"
ENV CRON_ENABLED="true"
ENV CRON_SCHEDULE="1 1 1 * *"


# Install Packages
RUN apk add mailx bash
RUN apk add --update busybox-suid

# Prepare App
RUN mkdir /app
COPY src/entrypoint.sh /app/entrypoint.sh
COPY src/export.php /app/export.php
COPY src/export.sh /app/export.sh
RUN chmod +x /app/*
RUN export PATH="$PATH:/app"

WORKDIR /app
ENTRYPOINT ["bash", "/app/entrypoint.sh"]

