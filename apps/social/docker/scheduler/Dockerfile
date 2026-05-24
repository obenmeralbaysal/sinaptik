# Scheduler — reuses the backend runtime image, swaps the entrypoint.
ARG BACKEND_IMAGE=sinapp-backend:latest
FROM ${BACKEND_IMAGE}

WORKDIR /var/www/html

ENTRYPOINT []
CMD ["php", "artisan", "schedule:work"]
