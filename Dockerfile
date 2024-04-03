FROM phpipam/phpipam-www:1.6x

ENV EXPORT_FROM="export@example.com"
ENV EXPORT_TO="export@example.com"
ENV CRON_ENABLED="true"
ENV CRON_SCHEDULE="1 1 1 * *"


# Prepare App
RUN mkdir /app
COPY src/* /app
RUN chmod +x /app/*


WORKDIR /app
USER 1000
ENTRYPOINT ["/app/entrypoint.sh"]
