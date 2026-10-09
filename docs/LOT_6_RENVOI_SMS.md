# Lot 6 — renvoi du code SMS marchand (2026-10-09)

## Plan avant correction

1. Reproduire une demande API de renvoi et compter les SMS mis en file.
2. Retirer le double appel du contrôleur, sans changer le transport SMS (D13).
3. Bloquer les interactions concurrentes dans l'écran pendant le renvoi.
4. Vérifier le code envoyé, la connexion qui suit et les erreurs réseau.
5. Actualiser les documents Claude, tester, publier en PR et fusionner.

## Constat

`Api/V10/AuthController::resendOTP()` appelle le repository une première fois,
puis une seconde dans sa condition. Le repository génère et enregistre un code
à chaque appel et met son SMS en file. Une demande peut donc envoyer deux codes
successifs, le premier devenant invalide. L'app ne marque pas le renvoi en cours
et permet de répéter le clic ou de valider l'ancien code simultanément.

## Critères de validation

Reproduction avant correction : le test de renvoi échoue avec **deux jobs
`SendSms` au lieu d'un**. Le contrôleur est corrigé pour appeler une seule fois
le repository. L'écran a un état d'envoi dédié, bloque les actions concurrentes,
et réinitialise la saisie du code uniquement après succès. Les tests de l'écran
simulent une réponse différée et un échec réseau, sans augmenter les délais.

- Une demande API met exactement un SMS OTP en file ; son code correspond à
  celui conservé pour le compte et ouvre la session une seule fois.
- Pendant le renvoi, boutons et saisie sont désactivés ; le message d'information
  apparaît après réussite. Le champ ancien code est alors vidé.
- En cas d'erreur, le message est affiché, le champ n'est pas effacé et l'écran
  permet une nouvelle tentative.
- Aucun changement des routes, des limites de débit, de la durée de validité,
  du caractère à usage unique ou des passerelles SMS existantes.

La mise en file ne prouve pas la réception sur un téléphone. Après reconstruction
de l'app marchand, la recette doit vérifier la réception, le renvoi et la
validation du nouveau code sur appareil ; le lot 4 reste ouvert pour ce point.

## Validation

- Avant correction : reproduction échouée, deux SMS mis en file pour une demande.
- Après correction : **10 tests OTP/limiteur réussis, 87 assertions**.
- Suite Laravel complète : **1 447 tests réussis, 51 958 assertions**.
- Marchand : **59 tests réussis**, typage et lint réussis, export Android Hermes réussi.
- Aucun SMS réel envoyé depuis l'environnement de test ; aucun secret modifié.
