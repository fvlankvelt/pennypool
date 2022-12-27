# we would have liked to use an offcial php image
# but as those maintainers are braindead and refuse
# to provide images that listen on a non-privileged port
# (see https://github.com/docker-library/php/issues/94),
# we simply build a php container from scratch instead
FROM docker.io/library/alpine:latest as penny

RUN apk update
RUN apk add apache2 apache2-ctl php81-apache2
RUN apk add composer php81-session php81-pdo php81-pdo_sqlite

RUN sed -i 's/^\s*Listen 80/Listen 8080/i' /etc/apache2/httpd.conf
RUN sed -i 's/^\s*CustomLog/#&/i' /etc/apache2/httpd.conf
RUN cd /etc/apache2/conf.d/ && rm info.conf languages.conf userdir.conf
RUN echo "ServerName localhost" > /etc/apache2/conf.d/local.conf
RUN echo -e "CustomLog /dev/stdout combined\nErrorLog /dev/stderr" > /etc/apache2/conf.d/logging.conf

RUN /usr/sbin/apachectl configtest

RUN chmod 1777 /run/apache2

EXPOSE 8080

ENTRYPOINT ["/usr/sbin/httpd",  "-DFOREGROUND"]
