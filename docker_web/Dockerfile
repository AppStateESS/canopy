FROM centos:7
MAINTAINER "Ted Eberhard" <eberhardtm@appstate.edu>
ENV container docker
USER root
# install php7 when canopy core is ready
RUN rpm -Uvh http://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
RUN yum -y install php72w php72w-fpm php72w-common php72w-mysql php72w-pgsql php72w-devel php72w-pear php72w-gd php72w-pecl-memcache php72w-pspell php72w-snmp php72w-xmlrpc php72w-xml php72w-soap;
#RUN yum -y install php php-common php-mysql php-pgsql php-devel php-gd php-pecl-memcache php-pspell php-snmp php-xmlrpc php-xml php-pear;
RUN yum -y install phpmyadmin phpPgAdmin vim
RUN yum -y update
RUN yum -y install gcc make
RUN yum -y install nginx
#RUN pecl install Xdebug
ADD docker_conf/phpMyAdmin.conf /etc/httpd/conf.d/phpMyAdmin.conf
ADD docker_conf/phpPgAdmin.conf /etc/httpd/conf.d/phpPgAdmin.conf
ADD docker_conf/myadmin_config.inc.php /etc/phpMyAdmin/config.inc.php
ADD docker_conf/pgadmin_config.inc.php /etc/phpPgAdmin/config.inc.php
ADD docker_conf/xdebug.ini /etc/php.d/xdebug.ini

RUN mkdir /etc/nginx/conf.d/common

ADD docker_conf/error_page.conf /etc/nginx/conf.d/common/error_page.conf
ADD docker_conf/php.conf /etc/nginx/conf.d/common/php.conf
ADD docker_conf/secure_block.conf /etc/nginx/conf.d/common/secure_block.conf
ADD docker_conf/nginx.conf /etc/nginx/nginx.conf

ADD docker_web/docker-web-entry.sh /docker-entry.sh
EXPOSE 80
CMD ["-D","FOREGROUND"]
ENTRYPOINT ["/docker-entry.sh"]
