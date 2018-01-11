FROM docker-repo.swimsp.io:443/servers/qaserver:BASE-6.6-P-1-21-C-1-20-develop

RUN yum install -y -q -e 0 php56-php-pecl-rdkafka.x86_64

RUN if [[ -e /opt/remi/php56/root/usr/lib64/php/modules/rdkafka.so && ! -e /usr/lib64/php/modules/rdkafka.so ]] ; then \
	cp /opt/remi/php56/root/usr/lib64/php/modules/rdkafka.so /usr/lib64/php/modules/rdkafka.so ; \
	fi

RUN if [[ -e /opt/remi/php56/root/etc/php.d/40-rdkafka.ini && ! -e /etc/php.d/40-rdkafka.ini ]] ; then \
	cp /opt/remi/php56/root/etc/php.d/40-rdkafka.ini /etc/php.d/40-rdkafka.ini ; \
	fi


