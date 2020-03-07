FROM nginx:latest

ADD vhost.conf /etc/nginx/conf.d/default.conf