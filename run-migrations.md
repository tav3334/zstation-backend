# Commandes pour exécuter les migrations sur Railway

## Option 1: Via Railway Dashboard (Recommandé)
1. Allez sur https://railway.app
2. Sélectionnez votre projet
3. Cliquez sur le service backend
4. Allez dans l'onglet "Deployments"
5. Cliquez sur "Redeploy" pour forcer un nouveau déploiement

## Option 2: Via Railway CLI

### Installation de Railway CLI
```bash
npm install -g @railway/cli
```

### Login à Railway
```bash
railway login
```

### Lier le projet
```bash
cd C:\xampp\htdocs\larbackend
railway link
```

### Exécuter les migrations
```bash
railway run php artisan migrate --force
```

### Exécuter le seeder pour créer les utilisateurs test
```bash
railway run php artisan db:seed --class=TestUserSeeder --force
```

### Vérifier les utilisateurs
```bash
railway run php artisan tinker
```
Puis dans tinker:
```php
User::all();
exit
```

## Utilisateurs test créés
- **Admin**: admin@zstation.com / password
- **Agent**: agent@zstation.com / password
