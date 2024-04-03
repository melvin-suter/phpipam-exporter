FROM phpipam/phpipam-www:1.6x

ENV EXPORT_FROM="export@example.com"
ENV EXPORT_TO="export@example.com"
ENV CRON_ENABLED="true"
ENV CRON_SCHEDULE="1 1 1 * *"


# Install Packages
RUN apt-get update && \
    apt-get install -y cron

# Prepare Cron
RUN touch /var/log/cron.log &&
    touch /etc/cron.d/crontab
    chmod 0644 /etc/cron.d/crontab

# Prepare App
RUN mkdir /app
COPY src/* /app
RUN chmod +x /app/*


WORKDIR /app
USER 1000
ENTRYPOINT ["/app/entrypoint.sh"]
