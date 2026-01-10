# Guide de Déploiement ZSTATION

## 🎯 URLs de l'application

### Frontend (Vercel)
- **URL**: https://zstation-nine.vercel.app
- **Dashboard Vercel**: https://vercel.com/dashboard

### Backend (Railway)
- **URL**: https://zstation.up.railway.app
- **Dashboard Railway**: https://railway.app

---

## 🔑 Identifiants de test

### Compte Admin
```
Email: admin@zstation.com
Password: password
```

### Compte Agent
```
Email: agent@zstation.com  
Password: password
```

---

## ✅ Checklist de configuration Railway

### 1. Variables d'environnement obligatoires

Dans Railway → Votre projet → Variables :

```env
# Application
APP_NAME=ZStation
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zstation.up.railway.app

# Frontend CORS (TRÈS IMPORTANT!)
FRONTEND_URL=https://zstation-nine.vercel.app

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### 2. Variables MySQL (auto-générées par Railway)

Vérifiez que ces variables existent avec la syntaxe `${{MySQL.XXX}}` :

```env
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

⚠️ **IMPORTANT** : La syntaxe doit être `MySQL.` avec un `M` majuscule!

---

## 🔧 Configuration Vercel

Dans Vercel → zstation-frontend → Settings → Environment Variables :

```env
VITE_API_URL=https://zstation.up.railway.app/api
```

Environnements : Cochez **Production**, **Preview**, et **Development**

---

## 🧪 Tests après déploiement

### 1. Tester l'API Backend

```bash
curl https://zstation.up.railway.app/api/health
```

Réponse attendue :
```json
{
  "status": "ok",
  "message": "ZStation API is running",
  "timestamp": "...",
  "database": "zstation"
}
```

### 2. Tester les utilisateurs

```bash
curl https://zstation.up.railway.app/api/debug/users
```

Réponse attendue :
```json
{
  "success": true,
  "count": 2,
  "users": [
    {
      "id": 1,
      "name": "Admin",
      "email": "admin@zstation.com",
      "role": "admin"
    },
    {
      "id": 2,
      "name": "Agent",
      "email": "agent@zstation.com",
      "role": "agent"
    }
  ]
}
```

### 3. Tester la connexion

```bash
curl -X POST https://zstation.up.railway.app/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@zstation.com","password":"password"}'
```

Réponse attendue :
```json
{
  "message": "Connexion réussie",
  "user": {...},
  "token": "..."
}
```

---

## 🐛 Dépannage

### Problème : "Server Error" sur login

**Cause** : La base de données ou les utilisateurs n'existent pas

**Solution** :
1. Vérifiez les logs Railway (Deployments → Dernier déploiement → View Logs)
2. Cherchez le message : `✅ Test users created`
3. Si absent, redéployer manuellement

### Problème : CORS Error

**Cause** : `FRONTEND_URL` manquant ou incorrect

**Solution** :
1. Allez dans Railway → Variables
2. Ajoutez `FRONTEND_URL=https://zstation-nine.vercel.app`
3. Redéployez

### Problème : 404 sur toutes les routes API

**Cause** : Le cache de routes Laravel

**Solution** :
Railway devrait exécuter automatiquement :
```bash
php artisan route:cache
php artisan config:cache
```

---

## 📝 Commandes utiles Railway CLI

Si vous avez Railway CLI installé :

```bash
# Se connecter
railway login

# Lien vers le projet
railway link

# Voir les logs en temps réel
railway logs

# Exécuter des commandes
railway run php artisan migrate --force
railway run php artisan db:seed --class=TestUserSeeder --force
railway run php artisan route:list
```

---

## 🚀 Redéploiement

### Automatique (Recommandé)
Push sur GitHub déclenche un redéploiement automatique

### Manuel
1. Railway Dashboard
2. Sélectionner le service Laravel
3. Deployments → Dernier → "Redeploy"

---

## 💡 Notes importantes

- Les migrations s'exécutent automatiquement à chaque déploiement
- Les seeders s'exécutent automatiquement à chaque déploiement  
- Le cache est nettoyé à chaque déploiement
- Les logs sont disponibles pendant 24h sur Railway

---

Créé avec ❤️ pour ZStation
