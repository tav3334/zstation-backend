# Guide: Tarification par Match pour FIFA/PES

## Vue d'ensemble

Le système supporte maintenant **deux modes de tarification** pour les jeux FIFA et PES:

1. **Par Match** (`per_match`): Le client paie en fonction du nombre de matchs joués
2. **Par Temps** (`fixed`): Le client paie pour une durée fixe (comme les autres jeux)

## Structure de la Base de Données

### Nouveaux Champs

**Table `pricing_modes`:**
- ID 1: `fixed` - Prix Fixe (par temps)
- ID 2: `per_match` - Par Match

**Table `game_pricings`:**
- `duration_minutes` (nullable): Pour les tarifs par temps
- `matches_count` (nullable): Pour les tarifs par match
- `pricing_mode_id`: Référence au mode de tarification

**Table `game_sessions`:**
- `matches_played` (nullable): Nombre de matchs joués (rempli à la fin de la session)

## Configuration FIFA/PES

FIFA 24 a maintenant 4 tarifs disponibles:

### Mode Par Match
- 1 match = 6 DH
- Le client saisit le nombre de matchs à la fin

### Mode Par Temps
- 6 minutes = 6 DH
- 30 minutes = 10 DH
- 60 minutes = 20 DH

## Utilisation de l'API

### 1. Obtenir la liste des jeux et leurs tarifs

**Endpoint:** `GET /api/games`

**Réponse exemple pour FIFA 24:**
```json
{
  "id": 1,
  "name": "FIFA 24",
  "active": true,
  "pricings": [
    {
      "id": 10,
      "game_id": 1,
      "pricing_mode_id": 1,
      "duration_minutes": 6,
      "matches_count": null,
      "price": "6.00",
      "pricing_mode": {
        "id": 1,
        "code": "fixed",
        "label": "Prix Fixe"
      }
    },
    {
      "id": 11,
      "game_id": 1,
      "pricing_mode_id": 1,
      "duration_minutes": 30,
      "matches_count": null,
      "price": "10.00",
      "pricing_mode": {
        "id": 1,
        "code": "fixed",
        "label": "Prix Fixe"
      }
    },
    {
      "id": 12,
      "game_id": 1,
      "pricing_mode_id": 1,
      "duration_minutes": 60,
      "matches_count": null,
      "price": "20.00",
      "pricing_mode": {
        "id": 1,
        "code": "fixed",
        "label": "Prix Fixe"
      }
    },
    {
      "id": 13,
      "game_id": 1,
      "pricing_mode_id": 2,
      "duration_minutes": null,
      "matches_count": 1,
      "price": "6.00",
      "pricing_mode": {
        "id": 2,
        "code": "per_match",
        "label": "Par Match"
      }
    }
  ]
}
```

### 2. Démarrer une session

**Endpoint:** `POST /api/sessions/start`

**Mode Par Match:**
```json
{
  "machine_id": 1,
  "game_id": 1,
  "game_pricing_id": 13
}
```

**Mode Par Temps:**
```json
{
  "machine_id": 1,
  "game_id": 1,
  "game_pricing_id": 10
}
```

**Réponse:**
```json
{
  "message": "Session started",
  "session": {...},
  "start_time": "2026-01-17T13:00:00.000000Z",
  "pricing_mode": "per_match",
  "will_auto_stop_at": null
}
```

**Note:** `will_auto_stop_at` est null pour le mode par match car il n'y a pas d'arrêt automatique.

### 3. Vérifier le statut de la session

**Endpoint:** `GET /api/sessions/status/{id}`

**Réponse pour mode par match:**
```json
{
  "status": "active",
  "pricing_mode": "per_match",
  "elapsed_seconds": 1200,
  "elapsed_minutes": 20,
  "machine": "PS5 #1",
  "price_per_match": "6.00 DH",
  "matches_count": 1,
  "message": "Saisir le nombre de matchs joués à la fin"
}
```

**Réponse pour mode par temps:**
```json
{
  "status": "active",
  "pricing_mode": "fixed",
  "elapsed_seconds": 180,
  "elapsed_minutes": 3,
  "remaining_seconds": 1620,
  "remaining_minutes": 27,
  "will_auto_stop": false,
  "forfait": "30 min = 10.00 DH",
  "machine": "PS5 #1"
}
```

### 4. Arrêter une session

**Endpoint:** `POST /api/sessions/stop/{id}`

**Mode Par Match (REQUIS: matches_played):**
```json
{
  "matches_played": 3
}
```

**Réponse:**
```json
{
  "message": "Session stopped successfully",
  "session": {...},
  "price": 18.00,
  "duration_used": "25 min",
  "payment_ready": true,
  "matches_played": 3,
  "price_per_match": "6.00 DH",
  "calculation": "3 match(s) × 6.00 DH = 18.00 DH"
}
```

**Mode Par Temps (pas de matches_played requis):**
```json
{}
```

**Réponse:**
```json
{
  "message": "Session stopped successfully",
  "session": {...},
  "price": 10.00,
  "duration_used": "25 min",
  "duration_paid": "30 min",
  "forfait": "30 min = 10.00 DH",
  "payment_ready": true
}
```

## Auto-Stop

**Important:** Les sessions en mode **par match** ne s'arrêtent PAS automatiquement. Seules les sessions en mode **par temps** (fixed) s'arrêtent automatiquement quand le temps est écoulé.

La commande `php artisan sessions:auto-stop` vérifie uniquement les sessions en mode `fixed`.

## Workflow Frontend

### Pour FIFA/PES:

1. **Sélection du jeu**: L'utilisateur choisit FIFA ou PES

2. **Choix du mode**:
   - Afficher deux options: "Par Match" ou "Par Temps"

3. **Si "Par Match" est choisi:**
   - Démarrer la session avec `game_pricing_id` du mode per_match
   - Pendant le jeu: Afficher le temps écoulé et le prix par match
   - À la fin: Demander au client de saisir le nombre de matchs joués
   - Arrêter la session avec `matches_played`
   - Calculer: Prix = matches_played × 6 DH

4. **Si "Par Temps" est choisi:**
   - Afficher les durées disponibles (6, 30, 60 min)
   - Démarrer la session avec le `game_pricing_id` choisi
   - Fonctionne comme les autres jeux (auto-stop, forfait, etc.)

## Ajouter le Mode Par Match à d'autres jeux

Pour ajouter le mode par match à PES 2024 ou d'autres jeux:

```php
use App\Models\Game;
use App\Models\GamePricing;
use App\Models\PricingMode;

$game = Game::where('name', 'PES 2024')->first();
$perMatchMode = PricingMode::where('code', 'per_match')->first();

GamePricing::create([
    'game_id' => $game->id,
    'pricing_mode_id' => $perMatchMode->id,
    'duration_minutes' => null,
    'matches_count' => 1,
    'price' => 6.00
]);
```

## Migration et Seeding

### Pour appliquer les changements:

```bash
# Exécuter la migration
php artisan migrate --force

# Ajouter le mode par match à FIFA/PES
php artisan db:seed --class=MatchBasedPricingSeeder --force

# Mettre à jour les pricing modes existants
php artisan db:seed --class=GamePricingSeeder --force
```

## Fichiers Modifiés

1. **Migration:** `2026_01_17_130139_add_match_based_pricing_support.php`
2. **Models:**
   - `app/Models/GamePricing.php`
   - `app/Models/GameSession.php`
3. **Controllers:**
   - `app/Http/Controllers/Api/GameSessionController.php`
   - `app/Http/Controllers/GameController.php`
4. **Seeders:**
   - `database/seeders/MatchBasedPricingSeeder.php`
   - `database/seeders/GamePricingSeeder.php`
