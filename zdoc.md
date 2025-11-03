sudo update-alternatives --set php /usr/bin/php8.2
sudo update-alternatives --set php /usr/bin/php8.4



php bin/console doctrine:fixtures:load --append