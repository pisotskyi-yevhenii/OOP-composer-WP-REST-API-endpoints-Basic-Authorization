# OOP-composer-WP-REST-API-endpoints-Basic-Authorization
Two custom WP REST API endpoints with basic authentication (OOP aprouch). Autoload classes with composer
- POST /wp-json/stream/v1/uploadAttachment: Uploads a single file to wp-content/uploads/stream-api/year/month/, validates it, and returns the file URL. Requires authentication.
- POST /wp-json/stream/v1/sendEmail: Sends an HTML email to multiple recipients with a subject, body, and optional attachment URLs (validated). Requires authentication.
