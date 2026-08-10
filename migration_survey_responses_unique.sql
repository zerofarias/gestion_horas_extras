-- Evita respuestas duplicadas en encuestas identificadas (user_id NOT NULL).
-- Ejecutar tras migration_surveys.sql (#21).

DELETE sr1 FROM survey_responses sr1
INNER JOIN survey_responses sr2
  ON sr1.survey_id = sr2.survey_id
 AND sr1.user_id = sr2.user_id
 AND sr1.user_id IS NOT NULL
 AND sr1.id > sr2.id;

ALTER TABLE survey_responses
  ADD UNIQUE KEY uq_survey_user_response (survey_id, user_id);
