-- Données 100 % factices.
SET NAMES utf8mb4;

INSERT INTO users (id, username, email, password, role, bio) VALUES
(1, 'admin', 'admin@vuln-lab.local', '0d107d09f5bbe40cade3de5c71e9e9b7', 'admin', 'Administrateur du forum. Je modère, je ne mords pas.'),
(2, 'alice', 'alice@vuln-lab.local', '7c6a180b36896a0a8c02787eeafb0e4c', 'user', 'Développeuse web, fan de PHP et de café.'),
(3, 'bob', 'bob@vuln-lab.local', 'd8578edf8458ce06fbc5bb76a58c5ca4', 'user', 'Étudiant en réseaux, je bricole des homelabs.'),
(4, 'carol', 'carol@vuln-lab.local', 'e10adc3949ba59abbe56e057f20f883e', 'user', 'Je teste tout ce qui a un bouton.'),
(5, 'dave', 'dave@vuln-lab.local', 'f25a2fc72690b780b2a14e140ef6a9e0', 'user', 'Amateur de jeux de rôle et de Linux.'),
(6, 'eve', 'eve@vuln-lab.local', '0571749e2ac330a7455809c6b0e7af90', 'user', 'Curieuse de nature. Trop, parfois.');

INSERT INTO posts (id, user_id, title, body) VALUES
(1, 1, 'Bienvenue sur le forum', 'Bienvenue à tous. Merci de rester courtois et de lire les règles avant de poster.'),
(2, 2, 'Quel éditeur utilisez-vous ?', 'J''hésite entre VS Code et un éditeur en terminal. Vos retours ?'),
(3, 3, 'Mon premier homelab', 'J''ai monté un petit serveur avec un vieux PC. Je partage mes notes bientôt.'),
(4, 4, 'Bug sur la page de profil', 'L''avatar ne s''affiche pas chez moi, quelqu''un d''autre a le problème ?'),
(5, 5, 'Soirée jeux de rôle samedi', 'On organise une partie samedi soir, il reste deux places.'),
(6, 6, 'Conseils pour apprendre le réseau', 'Je débute, quels cours ou livres me conseillez-vous ?'),
(7, 2, 'PHP 8 : les nouveautés utiles', 'Les arguments nommés et match changent vraiment la lisibilité du code.'),
(8, 3, 'Sauvegardes : vous faites comment ?', 'Rsync + un disque externe, ça suffit pour un usage perso ?'),
(9, 1, 'Maintenance prévue dimanche', 'Le forum sera indisponible une heure dimanche matin pour maintenance.'),
(10, 5, 'Recommandation de séries', 'Je cherche une bonne série policière, vous avez des idées ?');

INSERT INTO comments (post_id, user_id, body) VALUES
(1, 2, 'Merci pour l''accueil, ravie d''être ici !'),
(2, 3, 'VS Code pour moi, les extensions sont pratiques.'),
(2, 5, 'Vim, mais il faut accepter la courbe d''apprentissage.'),
(3, 6, 'Super projet, tu utilises quelle distribution ?'),
(4, 1, 'Merci du signalement, je regarde ça.'),
(5, 4, 'Je suis partante pour samedi !'),
(6, 3, 'Commence par le modèle OSI, puis pratique avec Wireshark.'),
(8, 2, 'Pense aussi à tester la restauration, c''est la clé.'),
(9, 6, 'Merci de prévenir à l''avance.'),
(10, 4, 'Essaie une série scandinave, elles sont excellentes.');

INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES
(2, 3, 'Question sur ton homelab', 'Salut Bob, tu pourrais m''expliquer comment tu as configuré ton réseau ?'),
(3, 2, 'Re: Question sur ton homelab', 'Bien sûr, je t''écris un petit résumé ce week-end.'),
(1, 4, 'Ton signalement', 'Salut Carol, j''ai bien reçu ton signalement sur l''avatar, correctif en cours.'),
(4, 1, 'Re: Ton signalement', 'Merci l''admin, tiens-moi au courant.'),
(5, 6, 'Partie de samedi', 'Eve, tu viens samedi ? On commence à 20h.'),
(6, 5, 'Re: Partie de samedi', 'Oui avec plaisir, j''apporte des snacks.'),
(2, 1, 'Demande de modération', 'Bonjour, un message sur le forum me semble hors sujet, pouvez-vous y jeter un oeil ?'),
(1, 2, 'Re: Demande de modération', 'Merci Alice, c''est noté, je vérifie.'),
(1, 3, 'Maintenance dimanche', 'Bob, peux-tu m''aider à tester le forum après la maintenance ?'),
(3, 5, 'Prêt de livre', 'Dave, je te rends ton livre de réseau mardi.'),
(6, 1, 'Changement d''adresse e-mail', 'Bonjour, j''aimerais changer mon adresse e-mail, comment faire ?');
