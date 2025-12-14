#  Gestion des Événements — Application Web (PHP/MySQL)

Application web de **gestion d’événements** permettant de publier, consulter et suivre des événements (conférences, réunions, ateliers, etc.).  
Elle propose deux rôles (**Utilisateur** / **Admin**) et un **chatbot d’assistance** pour améliorer l’expérience de navigation.

---

## ✅ Fonctionnalités

### 👤 Espace Utilisateur
- Authentification : **inscription / connexion / déconnexion**
- Consulter les événements : **recherche**, **filtre par catégorie**, **tri par date**
- Voir **détails** d’un événement + **partage**
- Visualiser les événements sur :
  - **Carte** (localisation)
  - **Calendrier** (FullCalendar) + ouverture des détails via popup
- S’inscrire à un événement (avec contrôle de places)
- Gérer **mes inscriptions** :
  - liste des inscriptions
  - **annulation**
  - téléchargement de l’**invitation PDF + QR Code**
- **Notifications** : badge de non-lus + panneau de lecture (marquer comme lue)
- Options : **mode sombre** + **changement de langue (FR/EN)**

### 🛠️ Espace Admin
- Gestion des événements (**CRUD**)
  - ajout (image, catégorie, localisation via carte)
  - modification (notifier les inscrits en cas de changement)
  - suppression/annulation (notifier les inscrits)
  - listing
- Gestion des catégories (**CRUD**)
- Gestion participants / inscriptions : voir les inscrits par événement

### 🤖 Chatbot
- Assistant pour :
  - répondre aux questions (événements / inscriptions)
  - aider à la navigation (carte / calendrier / détails)

---

## 🧰 Technologies utilisées

- **Backend :** PHP (POO), PDO, sessions
- **Base de données :** MySQL
- **Frontend :** HTML5, CSS3, **Bootstrap 5**, JavaScript
- **Calendrier :** FullCalendar
- **Carte :** Leaflet + OpenStreetMap
- **PDF + QR Code :** génération d’invitations (PDF) + QR Code
- **Notifications :** système + compteur non-lus (badge)
- **Sécurité :** password_hash, requêtes préparées, htmlspecialchars, contrôle d’accès par rôles

---

## 📦 Installation (XAMPP)

### 1) Pré-requis
- XAMPP (Apache + MySQL)
- PHP ≥ 8.x recommandé

### 2) Cloner le projet
```bash
git clone <URL_DU_REPO>
