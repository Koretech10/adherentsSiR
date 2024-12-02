# Adhérents SiR
Gestion des adhérents et des partenaires de Switch in Reims

## Prérequis
- Serveur web (Apache, nginx...)
- PHP 8.3 ou ultérieur
- Extensions PHP suivantes : ctype, dom, filter, iconv, json, xml, mbstring, phar, tokenizer
- [Composer](https://getcomposer.org/download/)
- [Symfony CLI](https://symfony.com/download)
- Système de gestion de base de données (MariaDB, MySQL, PostgreSQL, SQLite...)

## Première installation
- Préparer le serveur web pour accueillir l'application PHP
- Créer une base de données vierge
- Cloner le dépôt Git dans le dossier web racine
    - Via SSH (préféré)
      ```shell
      git clone git@github.com:Koretech10/adherentsSiR.git .
      ```
    - Via HTTPS
      ```shell
      git clone https://github.com/Koretech10/adherentsSiR.git .
      ```
- Passer sur la branche main pour avoir la dernière version stable de l'application
```shell
git checkout main
```
- Dupliquer le fichier .env pour créer le fichier de variables d'environnement et renseigner les variables
```shell
cp .env .env.prod.local
```
- Installer les dépendances
```shell
symfony composer install --no-dev --optimize-autoloader
```
- Tester la migration de la base de données
```shell
symfony console doctrine:migrations:migrate --dry-run
```
- Si le dry-run réussi, migrer la base de données
```shell
symfony console doctrine:migrations:migrate
```
- Vider le cache de l'application
```shell
symfony console cache:clear
```
- Compiler les assets
```shell
symfony console asset-map:compile
```
- Initialiser l'utilisateur root pour vous connecter à l'application
```shell
symfony console root:manage
```

## Mise à jour
- Depuis le dossier racine web, récupérer la dernière version du dépôt Git
```shell
git checkout main
git reset --hard
git pull
```
- Dupliquer le fichier .env pour créer le fichier de variables d'environnement et renseigner les variables
```
mv .env.prod.local .env.prod.local.bkp
cp .env .env.prod.local
```
- Installer les dépendances
```shell
symfony composer install --no-dev --optimize-autoloader
```
- Tester la migration de la base de données
```shell
symfony console doctrine:migrations:migrate --dry-run
```
- Si le dry-run réussi, migrer la base de données
```shell
symfony console doctrine:migrations:migrate
```
- Vider le cache de l'application
```shell
symfony console cache:clear
```
- Compiler les assets
```shell
symfony console asset-map:compile
```