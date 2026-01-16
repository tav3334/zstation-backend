# Super Admin Setup Guide

## 🎯 Créer un utilisateur Super Admin

Pour tester la nouvelle interface Super Admin, vous devez d'abord créer un compte avec le rôle `super_admin`.

### Option 1: Via SQL Direct (Recommandé)

Connectez-vous à votre base de données Railway et exécutez:

```sql
-- Créer un nouveau super admin
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
    'Super Admin',
    'superadmin@zstation.ma',
    '$2y$12$LQv3c1yduq1/1sgPb7W7buV9X5pX9L0vKxXkqKqQ7iH1H6fJqG6sG', -- password: "password123"
    'super_admin',
    NOW(),
    NOW()
);

-- OU mettre à jour un utilisateur existant
UPDATE users
SET role = 'super_admin'
WHERE email = 'votre@email.com';
```

**Note**: Le hash ci-dessus correspond au mot de passe `password123`. Pour plus de sécurité, changez-le après la première connexion.

### Option 2: Via Artisan Tinker (Si vous avez accès au serveur)

```bash
php artisan tinker
```

Puis:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Super Admin',
    'email' => 'superadmin@zstation.ma',
    'password' => Hash::make('VotreMotDePasseSecurise'),
    'role' => 'super_admin'
]);
```

### Option 3: Via Migration (Pour environnement de développement)

Créez un seeder:

```bash
php artisan make:seeder SuperAdminSeeder
```

Éditez `database/seeders/SuperAdminSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@zstation.ma'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperSecure123!'),
                'role' => 'super_admin'
            ]
        );
    }
}
```

Exécutez:

```bash
php artisan db:seed --class=SuperAdminSeeder
```

## 🚀 Accéder à l'interface Super Admin

1. Allez sur https://zstation-nine.vercel.app/
2. Connectez-vous avec les identifiants super admin
3. Vous serez automatiquement redirigé vers le **Super Admin Panel**

## ✨ Fonctionnalités disponibles

### 👥 Gestion des Utilisateurs
- ✅ Créer nouveaux utilisateurs (Admin ou Agent)
- ✅ Modifier utilisateurs existants
- ✅ Supprimer utilisateurs
- ✅ Voir liste complète avec rôles
- ⚠️ Protection: Impossible de supprimer ou modifier son propre rôle

### 🖥️ Gestion des Machines
- ✅ Ajouter nouvelles machines/stations
- ✅ Modifier type et statut des machines
- ✅ Supprimer machines (si aucune session active)
- ✅ Voir statut en temps réel (Disponible/Occupée)

### 🎮 Gestion des Jeux
- ✅ Créer nouveaux jeux avec tarification
- ✅ Modifier prix pour 1h, 2h, 3h, nuit complète
- ✅ Supprimer jeux
- ✅ Voir catalogue complet avec prix

## 🔐 Sécurité

### Routes protégées
Toutes les routes Super Admin sont protégées par:
- `auth:sanctum` - Authentification requise
- `role:super_admin` - Rôle super_admin requis

### Endpoints API
```
GET    /api/super-admin/users
POST   /api/super-admin/users
GET    /api/super-admin/users/{id}
PUT    /api/super-admin/users/{id}
DELETE /api/super-admin/users/{id}

GET    /api/super-admin/machines
POST   /api/super-admin/machines
GET    /api/super-admin/machines/{id}
PUT    /api/super-admin/machines/{id}
DELETE /api/super-admin/machines/{id}

GET    /api/super-admin/games
POST   /api/super-admin/games
GET    /api/super-admin/games/{id}
PUT    /api/super-admin/games/{id}
DELETE /api/super-admin/games/{id}
```

## 🧪 Tester la fonctionnalité

### 1. Créer un utilisateur Agent
```
Nom: John Doe
Email: agent@test.com
Mot de passe: test1234
Rôle: Agent
```

### 2. Créer un utilisateur Admin
```
Nom: Jane Smith
Email: admin@test.com
Mot de passe: test1234
Rôle: Admin
```

### 3. Créer une machine
```
Numéro: 5
Type: PS5 Pro
Statut: Disponible
```

### 4. Créer un jeu
```
Nom: FIFA 25
Prix 1h: 15 DH
Prix 2h: 25 DH
Prix 3h: 35 DH
Nuit complète: 50 DH
```

### 5. Tester la suppression
- ✅ Supprimer un utilisateur → Devrait fonctionner
- ❌ Supprimer votre propre compte → Devrait être bloqué
- ❌ Modifier votre propre rôle → Devrait être bloqué

## 🎨 Interface

L'interface Super Admin est complètement séparée des dashboards Admin et Agent:
- **Design**: Gradient violet-bleu moderne
- **Navigation**: Onglets pour Users/Machines/Games
- **Modals**: Formulaires contextuels pour création/édition
- **Responsive**: Fonctionne sur mobile, tablette et desktop

## 📊 Hiérarchie des rôles

```
Super Admin (super_admin)
    ↓
    ├─ Gestion complète des utilisateurs
    ├─ Gestion des machines
    ├─ Gestion des jeux
    └─ Toutes les permissions Admin + Agent

Admin (admin)
    ↓
    ├─ Voir statistiques avancées
    ├─ Gérer les produits
    ├─ Voir tous les paiements
    └─ Toutes les permissions Agent

Agent (agent)
    ↓
    ├─ Démarrer/Arrêter sessions
    ├─ Gérer les paiements
    ├─ Vendre des produits
    └─ Voir statistiques du jour
```

## 🐛 Debugging

Si vous ne voyez pas l'interface Super Admin après connexion:

1. **Vérifiez le rôle dans la base de données**:
   ```sql
   SELECT id, name, email, role FROM users WHERE email = 'votre@email.com';
   ```
   Le rôle doit être exactement `super_admin` (pas `superadmin` ou `super-admin`)

2. **Vérifiez le token dans localStorage**:
   - Ouvrez les DevTools (F12)
   - Onglet Application → Local Storage
   - Vérifiez que `user` contient `"role":"super_admin"`

3. **Testez l'API directement**:
   ```bash
   curl -X GET https://votre-backend.railway.app/api/super-admin/users \
     -H "Authorization: Bearer VOTRE_TOKEN"
   ```

4. **Vérifiez les logs Railway**:
   - Si erreur 403: Le rôle n'est pas super_admin
   - Si erreur 401: Token invalide ou expiré
   - Si erreur 500: Problème côté serveur

## 📝 Notes importantes

- ⚠️ Un seul super admin suffit généralement pour l'application
- 🔒 Gardez les identifiants super admin en sécurité
- 📧 Utilisez un email professionnel pour le super admin
- 🔑 Changez le mot de passe par défaut immédiatement
- 💾 Faites des backups réguliers avant modifications importantes

## 🎉 C'est prêt!

Votre système Super Admin est maintenant configuré et déployé sur:
- **Frontend**: https://zstation-nine.vercel.app/
- **Backend**: Railway (avec auto-déploiement depuis GitHub)

Profitez de votre nouvel outil de gestion! 🚀
