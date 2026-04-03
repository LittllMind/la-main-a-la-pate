📄 Procédure standard pour déployer une mise à jour sur Hostinger
Application Laravel – domaine : la-main-a-la-pate.online

1. Pré‑requis

Dépôt Git (GitHub, GitLab, etc.) avec au moins :
branche master (développement stabilisé),
branche production (ce qui est en ligne).


Accès SSH à Hostinger (port, user, mot de passe déjà configurés).
Accès FTP (FileZilla) pour uploader le dossier public/build.
Sur le PC local :
PHP / Composer,
Node / NPM (pour npm run build),
Git.


Sur le serveur Hostinger :
PHP / Composer disponibles en ligne de commande.




2. Règles générales

On ne code jamais directement sur le serveur.
Seule la branche production doit être déployée en prod.
Toute nouvelle fonctionnalité passe par une branche de feature → fusion dans master → fusion dans production.


3. Workflow Git côté local
3.1 Créer une branche de feature
# Se mettre à jour sur master
git checkout master
git pull origin master

# Créer la branche de travail
git checkout -b feature/ma-nouvelle-fonction
Coder, tester en local (navigateur, tests éventuels).
3.2 Commit de la feature
git status
git add .
git commit -m "Ajoute XXX sur la page YYY"
3.3 Fusionner dans master
git checkout master
git merge --no-ff feature/ma-nouvelle-fonction
git push origin master
Optionnel : supprimer la branche de feature.
git branch -d feature/ma-nouvelle-fonction
git push origin --delete feature/ma-nouvelle-fonction  # si déjà poussée

3.4 Mettre à jour la branche production
git checkout production
git pull origin production          # récupère le dernier état distant
git merge --no-ff master            # intègre ce qui a été validé sur master
git push origin production          # envoie la prod vers GitHub
À ce stade, origin/production = version à déployer sur Hostinger.

4. Build des assets front (Vite)

Sur Hostinger, on ne lance pas npm run build.Le build est fait sur le PC local, puis uploadé.

Depuis la racine du projet sur ton PC :
npm install          # ou npm ci si déjà locké
npm run build
Cela génère :
public/build/manifest.json
public/build/assets/...
Ensuite, avec FileZilla :

Panneau gauche (local) : .../ton-projet/public/build

Panneau droit (distant) :/home/u417457839/domains/la-main-a-la-pate.online/public_html/public

Uploader tout le dossier build local vers public_html/public/(remplacer le contenu existant si besoin).


Vérification rapide dans le navigateur (optionnel) :
https://la-main-a-la-pate.online/public/build/manifest.json
→ Doit répondre par un JSON (ou téléchargement), pas une 404.

5. Déploiement sur Hostinger (SSH)
5.1 Connexion
ssh u417457839@la-main-a-la-pate.online -p 65002
cd /home/u417457839/domains/la-main-a-la-pate.online/public_html
(Adapter l’utilisateur / port si besoin.)
5.2 Mode script (recommandé)
Créer une fois un fichier deploy.sh (si pas déjà fait) :
nano deploy.sh
Contenu :
#!/bin/bash
set -e

echo "==> Go to project directory"
cd /home/u417457839/domains/la-main-a-la-pate.online/public_html

echo "==> Git: checkout production & pull"
git fetch origin
git checkout production
git pull origin production

echo "==> Composer install (prod)"
composer install --no-dev --optimize-autoloader

echo "==> Migrations"
php artisan migrate --force

echo "==> Clear & cache Laravel"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

echo "==> Deployment finished."
Rendre exécutable :
chmod +x deploy.sh
Ensuite, à chaque mise à jour :
ssh u417457839@la-main-a-la-pate.online -p 65002
cd /home/u417457839/domains/la-main-a-la-pate.online/public_html
./deploy.sh

6. Vérifications après déploiement

Tester rapidement les pages principales :

https://la-main-a-la-pate.online/
https://la-main-a-la-pate.online/login
1 ou 2 actions métiers importantes (ex : une création de vente de test).


Ouvrir la console navigateur :

vérifier qu’il n’y a pas de 404 sur les fichiers /build/assets/...,
vérifier l’absence d’erreur JS majeure.


Vérifier que l’appli est bien en mode prod :

Dans .env sur le serveur (via FTP/éditeur Hostinger) :
APP_ENV=production
APP_DEBUG=false

Puis en SSH :
cd /home/u417457839/domains/la-main-a-la-pate.online/public_html
php artisan config:clear
php artisan config:cache





7. Recommandations pour la base de données
Comme les données sont sensibles (stocks, ventes) :

Avant une migration importante (ajout/suppression de colonnes, modifications de clés, etc.) :

tester la migration en local sur une copie de la base,
faire un export complet de la base de prod (via phpMyAdmin ou équivalent),
ensuite seulement exécuter php artisan migrate --force en prod.


Éviter dans la mesure du possible :

dropIfExists / truncate sur des tables de production sans plan de backup / rollback.




8. Résumé ultra‑rapide (checklist)
À chaque nouvelle version :

Local  

coder sur une branche feature/..., tester  
merge → master  
merge master → production, push


Local (front)  

npm run build  
uploader public/build vers public_html/public/ (FTP)


Serveur (SSH)  

./deploy.sh


Tests rapides en prod (pages clé, ventes de test, console navigateur).



Si tu veux, tu peux me dire sur quoi portera ta prochaine vraie modification, et on applique ce document ensemble une première fois pour être sûr que tout est carré.
