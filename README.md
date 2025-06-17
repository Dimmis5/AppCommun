# 🌙 Projet Domotique – Scénario Rideau et Lampe

## 🎯 Objectif du projet

Ce projet vise à réaliser un système domotique intelligent permettant de **fermer automatiquement un rideau et d’allumer une lampe** en fonction de la **température extérieure** et de la **luminosité ambiante**, tout en respectant les contraintes d'un projet mêlant électronique, informatique et travail en équipe.

## 🧩 Description du scénario

Nous avons choisi le cas d’usage **gestion domotique**. Notre scénario automatisé fonctionne ainsi :

- Lorsque la **luminosité extérieure baisse** sous un certain seuil (début de soirée), **le rideau se ferme automatiquement**.
- Une fois le rideau fermé (détecté par un **capteur de fin de course**), **la lampe s’allume** via un moteur servo connecté à un **interrupteur mécanique**.
- En parallèle, le **capteur de température extérieur** permet d’adapter ou d’enrichir la logique dans de futures extensions.

## 🛠️ Composants utilisés

| Composant                 | Rôle                                                                 |
|--------------------------|----------------------------------------------------------------------|
| Moteur pour rideau        | Actionneur pour ouvrir/fermer le rideau                               |
| Moteur servo              | Actionneur pour activer mécaniquement l'interrupteur de la lampe      |
| Capteur de luminosité     | Mesure l’intensité lumineuse ambiante                                 |
| Capteur de température    | Fournit la température extérieure                                     |
| Capteur de fin de course  | Détecte la fermeture complète du rideau                               |
| Microcontrôleur (TIVA) | Interface entre les capteurs/actionneurs et l’ordinateur              |

## 🖥️ Partie Informatique

- **Site Web** développé en HTML/CSS/JavaScript/PHP avec une base de données MySQL.
- Le site permet :
  - Connexion / inscription des utilisateurs
  - Visualisation des données capteurs en temps réel
  - Contrôle des actionneurs (manuel ou automatique)
- Communication série entre le microcontrôleur et le serveur via PHP.
- Stockage des données dans une **base de données partagée** accessible aux autres équipes.

## 📊 Fonctionnalités Bonus

- Graphiques dynamiques de la luminosité et température (avec Chart.js)
- Mode "test" avec données fictives pour démonstration
- Eco-conception du site (design sobre, compression des ressources)
- Sécurité : mot de passe hashé, gestion des sessions, protection CSRF

## 🔒 Sécurité et Accessibilité

- Accès sécurisé par login
- Interface responsive et adaptée à différents types d’utilisateurs
- Diagnostic éco-responsable effectué avec des outils en ligne (ex : EcoIndex)

## 🧑‍🤝‍🧑 Équipe & Organisation

- Travail en équipe :
  - ESSOME Maeva Chloe
  - JEANNINGROS Esteban
  - POULAIN Alexandre
  - REYNAL DE SAINT MICHEL Hortense
  - ZHANG Dimeo
- Répartition des rôles :
  - Électronique : câblage, programmation microcontrôleur
  - Informatique : développement site web, base de données, design UI
  - Documentation & tests : rédaction du livrable, vérification du bon fonctionnement

## 📅 Présentation finale

- Soutenance prévue le **vendredi 20 juin 2025**
- Présentation du cas d’usage, du système développé et démonstration live
- Évaluation sur la technique, l’organisation, la créativité et la soutenance orale
