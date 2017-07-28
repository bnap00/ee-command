FROM php:7.0.21-alpine
MAINTAINER Bharat Parsiya <bnap00@gmail.com>

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x *.phar
RUN mv wp-cli.phar /usr/local/bin/wp
RUN php -d memory_limit=256M /usr/local/bin/wp package install git@github.com:Sidsector9/ee-command.git --allow-root

ENTRYPOINT [""]
CMD ["wp"]