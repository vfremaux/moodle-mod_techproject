This "techproject" activity module is intended to give a complete project driving environement in a pedagogical environement, allowing to teach first principles of pragmatic project management to students through standard steps that are collecting needs, specifying solution, and controlling developement tasks. Its goal is to fit moodle with a fully featured project management tool that could elsewhere be used to manage real projects. Teacher/students interaction will be based on state-of-the art practices in Moodle and using most of the core tools and API we can.

Contenu :
#########

Ce module contient toutes les ressources nécessaires à son fonctionnement. Cependant, pour la première version, certains sous-composants du module doivent être installés à la main.

Ce module contient : 

- les fichiers php et annexes nécessaires au fonctionnement d'un module Moodle standard.

- des fichiers de langue pour les labels et les textes. Vous y trouverez un fichier de labels et un répertoire d'aide pour chaque langue fournie.

- un ensemble d'icônes dédiées dans un répertoire "pix/p/" directory.


Installation :
##############

Une fois l'archive décompactée dans le répertoire {$CFG->dirroot}/mod de votre implémentation de Moodle,

1. Installation des labels et textes

copiez les différentes ressources de langue (fournies dans le répertoire /lang du module) dans les répertoires appropriés du container central des ressources linguistiques : {$CFG->dirroot}/lang. En version 1.7, vous pouvez également avoir un paquetage de langue dans le répertoire "moodledata" si vous avez installé les langues automatiquement. Une dernière alternative, à partir de la version 1.7+ est de laisser les fichiers de langue là où ils sont.

2. Déplacement du répertoire d'icônes

Copiez le répertoire pix/p/ de la distribution dans le répertoire aproprié des icones de Moodle.

Lancement, Paramétrage : 
########################

Pour lancer le module, il faut en faire l'intallation "logique". Conectez-vous à Moodle en mode administrateur, et allez sur l'interface d'administration. 

Le modèle de données du module techproject est installé automatiquement.

Allez dans les écrans de configuration des modules : vous devez voir un nouveau module "techproject". Activez-le si nécessaire. 

Reportez vous à l'aide du module et des paramètres pour activer ou désactiver certaines options.

Trying the module:
##################

Dans un cours test, ajoutez une instance du module techproject, naviguez dans le module et profitez de la visite.

Module instance parametrization:
################################

Une nouvelle instance d'un module techproject dispose de quelques paramètres pour altérer son comportement. Vous pouvez définir les dates de début et de fin du projet, laisser les étudioants éditer une ou plusieurs des entités de description du projet, paramétrer la façon dont le module va proposer les évaluation, et ainsi de suite.

Roadmap: 
########

Voici la liste des quelques développements supplémentaires prévus. 

Etape 1 : May 2007 - consolidation

Infrastructure
   Revue et amélioration des notification
   Revue et amélioration de la journalisation
Edition des entités
   Vérification de contraintes sur les dates
   Nettoyage des listes d'association
Livrables
   Attachementde fichiers sur les livrables ... EFFECTUE
Affichage des entités
   Amélioration des indicateurs et des propagations
Outils de l'enseignant
   Imports/exports en XML de projets
   Export/publication en XML simple pour impression ... PARTIEL
   File de messages pour donner des feedback et des commentaires aux groupes de projet

Step 2 : Encore plus d'outils et d'améliorations

Outils additionnels
   Diagrammes de Gantt et de Perth ... GANTT EFFECTUE
   Liste de TODO (petites tâches/actions en liste). Peut servir de bugtracker. 
   Pilotage de référentiels CVS




Essai d'un module :
###################

Dans un cours d'expérimentation, ajouter un exemplaire du module coursetracking. Ce module fonctionne même s'il n'est pas visible pour les étudiants (l'activation désactivation des fonctions du module se font par le paramétrage du module lui-même).

Editez une ressource HTML avec l'éditeur whiziwhyg. Passez l'éditeur en plein écran. Vous devez avoir sur la droite de la première ligne de boutons un nouveau bouton. Ce bouton déclenche l'insertion d'un capteur à travers une boîte de dialogue. Il permet également, si un capteur est sélectionné, de modifier ses propriétés.

Pour collecter des captures, il faut se loguer en mode étudiant dans le même cours. Le filtre, s'il fonctionne convertit les images-capteurs en micro-formulaires interactifs et asynchrones.
Des événements sont captés dès le "passage" sur les capteurs.

Paramétrage d'un module :
#########################

Un nouveau module de tracking dispose de quelques paramètres pour contrôler son fonctionnement et son application. Vous pourrez définir une plage d'action des capteurs, et également une plage de compilation des résultats. Par défaut, ces plages ne sont pas activées et les capteurs émettent des événements dès qu'ils sont placés dans le contenu. De même, la compilation prend, par défaut, tous les événements enregistrés pour les capteurs du cours.

Plusieurs modules dans un même cours :
######################################

Il est possible d'utiliser plusieurs instances d'un module de tracking dans un seul cours. Dès lors, les capteurs implantés dans le contenu peuvent être attribués à n'importe quel instance de tracking dans ce cours. On répartira alors les capteurs suivant le projet de mesure à faire. Il sera possible, par exemple, d'obtenir des rapports séparés pour des documents à lecture obligatoire, et des documents à lecture facultative.

Roadmap : 
#########

Ce chapitre vous donne des indications sur les dévelopements qui sont envisagées dans le futur. 

- Voir l'effet du capteur en mode élève sur le contenu (sans etre obligé de se loguer à la place d'un élève) (patch à venir pour version > 1.6).
- optimiser la stratégie de cache et les exceptions qu'il convient de contrôler.
- permettre l'insertion facile d'un capteur sur des articles "texte". 
- permettre l'insertion dans des textes d'activité.
- améliorer et augmenter les fonctionnalités des rapports.
- terminer la moodlisation des récepteurs Ajax, notamment le respect de l'API libdata.php
- sécuriser les récepteurs Ajax.