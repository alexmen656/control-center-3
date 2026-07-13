server {
    listen 80;
    server_name __HOST__;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name __HOST__;

    ssl_certificate /etc/letsencrypt/live/__HOST__/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/__HOST__/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/deploys/__SLUG__/current;
    index index.html;

    add_header X-Content-Type-Options nosniff always;

    location ~ \.php$ {
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }
}
