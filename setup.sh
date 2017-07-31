docker-compose up -d
docker-compose exec wordpress apt update
docker-compose exec wordpress apt install less git subversion
docker-compose exec wordpress chsh www-data -s /bin/bash
docker-compose exec wordpress chown www-data:www-data . -R
docker-compose exec wordpress curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
docker-compose exec wordpress chmod +x wp-cli.phar
docker-compose exec wordpress mv wp-cli.phar /usr/local/bin/wp
docker-compose exec --user www-data wordpress php -d memory_limit=256M wp package install git@github.com:Sidsector9/ee-command.git
alias eewp='docker-compose exec --user www-data wordpress wp'
