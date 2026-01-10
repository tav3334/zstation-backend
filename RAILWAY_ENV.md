# Variables d'environnement requises pour Railway

## Configuration de base

```env
APP_NAME=ZStation
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zstation.up.railway.app

# Base de données (auto-générées par Railway)
DB_CONNECTION=mysql
DB_HOST=${{MYSQL.HOST}}
DB_PORT=${{MYSQL.PORT}}
DB_DATABASE=${{MYSQL.DATABASE}}
DB_USERNAME=${{MYSQL.USER}}
DB_PASSWORD=${{MYSQL.PASSWORD}}

# CORS - TRÈS IMPORTANT pour la connexion frontend
FRONTEND_URL=https://zstation-frontend.vercel.app

# Session et Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

## ⚠️ Configuration CORS importante

Après avoir ajouté `FRONTEND_URL`, vous devez redéployer l'application sur Railway.

## 🔄 Comment redéployer

1. Allez sur railway.app
2. Sélectionnez votre projet
3. Cliquez sur "Deployments"
4. Cliquez sur "Redeploy"

OU

Push un changement sur GitHub (Railway redéploie automatiquement)
