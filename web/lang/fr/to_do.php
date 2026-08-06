<?php

use App\Enums\TodoStatus;

return [

  'dashboard' => 'Tableau de bord',
  'to_do' => 'Tâche à faire',
  'to_do_add' => 'Ajouter une tâche à faire',
  'to_do_edit' => 'Modifier la tâche à faire',
  'to_do_delete' => 'Supprimer la tâche à faire',
  'to_do_list' => 'Liste des tâches à faire',
  'title' => 'Titre',
  'description' => 'Description',
  'assign' => 'Assigner',
  'date' => 'Date',
  'status' => 'Statut',
  'action' => 'Action',
  'delete' => 'Supprimer',
  'sl' => 'SL',
  'status_update' => 'Mise à jour du statut',
  'added_msg' => 'Tâche à faire ajoutée avec succès !',
  'error_msg' => 'Oops ! Quelque chose s\'est mal passé',
  'note' => 'Note',
  TodoStatus::PENDING => 'En attente',
  TodoStatus::PROCESSING => 'En cours de traitement',
  TodoStatus::COMPLETED => 'Terminé',
  'todo_processing_success' => 'Tâche à faire en cours de traitement avec succès',
  'todo_compete_success' => 'Tâche à faire terminée avec succès',
  'update_msg' => 'Tâche à faire mise à jour avec succès !',

];